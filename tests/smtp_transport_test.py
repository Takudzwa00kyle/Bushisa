"""Exercise the real PHP transport against a local SMTP fixture; no emails leave localhost."""

import base64
import os
from pathlib import Path
import socket
import ssl
import subprocess
import sys
import tempfile
import threading


REPO = Path(__file__).resolve().parents[1]
PHP = os.environ.get("BUSHISA_PHP_BINARY", "php")


def php_path(path):
    # Cygwin Python can read POSIX /tmp paths; native Windows PHP needs the
    # corresponding Windows path for openssl.cafile. Linux CI is unchanged.
    if sys.platform == "cygwin" and PHP.lower().endswith(".exe"):
        return subprocess.check_output(["cygpath", "-w", str(path)], text=True).strip()
    return str(path)


def openssl(*args):
    subprocess.run(["openssl", *args], check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)


def exercise(cert_dir, advertise_tls, trust_ca=True):
    listener = socket.socket()
    listener.bind(("127.0.0.1", 0))
    listener.listen(1)
    listener.settimeout(10)
    port = listener.getsockname()[1]
    observed = {"tls": False, "auth": False, "recipients": [], "data": b""}
    errors = []

    def server():
        connection = None
        try:
            connection, _ = listener.accept()
            connection.settimeout(5)
            stream = connection.makefile("rwb", buffering=0)
            stream.write(b"220 localhost synthetic SMTP\r\n")
            auth_step = 0
            while line := stream.readline():
                command = line.decode("ascii").strip()
                if auth_step == 1:
                    assert base64.b64decode(command) == b"synthetic-user"
                    stream.write(b"334 UGFzc3dvcmQ6\r\n")
                    auth_step = 2
                elif auth_step == 2:
                    assert base64.b64decode(command) == b"synthetic-password"
                    observed["auth"] = True
                    stream.write(b"235 2.7.0 authenticated\r\n")
                    auth_step = 0
                elif command.startswith("EHLO "):
                    if advertise_tls and not observed["tls"]:
                        stream.write(b"250-localhost\r\n250-STARTTLS\r\n250 AUTH LOGIN\r\n")
                    else:
                        stream.write(b"250-localhost\r\n250 AUTH LOGIN\r\n")
                elif command == "STARTTLS":
                    if not advertise_tls:
                        stream.write(b"454 4.7.0 TLS unavailable\r\n")
                        continue
                    stream.write(b"220 2.0.0 begin TLS\r\n")
                    context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
                    context.minimum_version = ssl.TLSVersion.TLSv1_2
                    context.load_cert_chain(cert_dir / "server.pem", cert_dir / "server.key")
                    stream.close()
                    connection = context.wrap_socket(connection, server_side=True)
                    observed["tls"] = True
                    stream = connection.makefile("rwb", buffering=0)
                elif command == "AUTH LOGIN":
                    assert observed["tls"], "credentials must never be sent before TLS"
                    stream.write(b"334 VXNlcm5hbWU6\r\n")
                    auth_step = 1
                elif command.startswith("MAIL FROM:"):
                    assert observed["auth"]
                    observed["from"] = command
                    stream.write(b"250 2.1.0 sender accepted\r\n")
                elif command.startswith("RCPT TO:"):
                    observed["recipients"].append(command)
                    stream.write(b"250 2.1.5 recipient accepted\r\n")
                elif command == "DATA":
                    stream.write(b"354 end with dot\r\n")
                    while (part := stream.readline()) != b".\r\n":
                        if not part:
                            raise AssertionError("incomplete message")
                        observed["data"] += part
                    stream.write(b"250 2.0.0 accepted locally\r\n")
                elif command == "QUIT":
                    stream.write(b"221 2.0.0 bye\r\n")
                    break
                else:
                    stream.write(b"250 2.0.0 ok\r\n")
        except ssl.SSLError:
            if trust_ca:
                errors.append("trusted fixture failed TLS")
        except Exception as exc:
            errors.append(str(exc))
        finally:
            if connection:
                connection.close()
            listener.close()

    thread = threading.Thread(target=server, daemon=True)
    thread.start()
    environment = dict(os.environ, SMTP_HOST="127.0.0.1", SMTP_PORT=str(port),
                       SMTP_USERNAME="synthetic-user", SMTP_PASSWORD="synthetic-password",
                       SMTP_ENCRYPTION="tls", SMTP_TIMEOUT_SECONDS="2",
                       MAIL_FROM_ADDRESS="donotreply@bushisa.me", MAIL_FROM_NAME="Bushisa",
                       MAIL_REPLY_TO="")
    program = """
require getcwd() . '/php/mail_delivery.php';
$payload = ['recipient' => 'synthetic@students.nust.ac.zw',
    'from_address' => 'donotreply@bushisa.me', 'subject' => 'Synthetic SMTP check',
    'body' => 'Your Bushisa approval code is 001234.'];
try {
    bushisa_send_smtp_payload($payload, 17, 42);
    echo 'accepted';
} catch (BushisaMailTransportException $e) {
    echo 'rejected';
}
"""
    command = [PHP]
    if trust_ca:
        command += ["-d", "openssl.cafile=" + php_path(cert_dir / "ca.pem")]
    command += ["-r", program]
    result = subprocess.run(command, cwd=REPO, env=environment, capture_output=True, text=True, timeout=15)
    thread.join(6)
    assert not thread.is_alive(), "fixture must finish"
    assert result.returncode == 0 and not errors, (result.stderr, errors)
    if advertise_tls and trust_ca:
        assert result.stdout == "accepted", result.stdout
        assert observed["tls"] and observed["auth"]
        assert observed["recipients"] == ["RCPT TO:<synthetic@students.nust.ac.zw>"]
        assert observed["from"] == "MAIL FROM:<donotreply@bushisa.me>"
        assert b"001234" in observed["data"], "leading zeroes must survive transport"
        assert b"Message-ID: <signup-42-17@bushisa.me>" in observed["data"]
        assert b"\r\nBcc:" not in observed["data"]
    else:
        assert result.stdout == "rejected", result.stdout
        assert not observed["auth"] and not observed["data"], "failure must not expose credentials or the code"


with tempfile.TemporaryDirectory(prefix="bushisa-smtp-") as directory:
    cert_dir = Path(directory)
    openssl("req", "-x509", "-newkey", "rsa:2048", "-nodes", "-days", "1",
            "-subj", "/CN=Synthetic Bushisa Test CA",
            "-keyout", str(cert_dir / "ca.key"), "-out", str(cert_dir / "ca.pem"))
    openssl("req", "-newkey", "rsa:2048", "-nodes", "-subj", "/CN=localhost",
            "-keyout", str(cert_dir / "server.key"), "-out", str(cert_dir / "server.csr"))
    extensions = cert_dir / "extensions.cnf"
    extensions.write_text("subjectAltName=DNS:localhost,IP:127.0.0.1\nbasicConstraints=CA:FALSE\nextendedKeyUsage=serverAuth\n")
    openssl("x509", "-req", "-in", str(cert_dir / "server.csr"),
            "-CA", str(cert_dir / "ca.pem"), "-CAkey", str(cert_dir / "ca.key"),
            "-CAcreateserial", "-days", "1", "-extfile", str(extensions), "-out", str(cert_dir / "server.pem"))
    exercise(cert_dir, advertise_tls=True)
    exercise(cert_dir, advertise_tls=False)
    exercise(cert_dir, advertise_tls=True, trust_ca=False)

print("smtp_transport_test PASS")
