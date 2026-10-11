<?php

declare(strict_types=1);

require_once __DIR__ . '/../php/signup_verification.php';

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

assert_true(bushisa_student_id_is_valid('n02531234f'), 'valid NUST student number should pass');
assert_true(!bushisa_student_id_is_valid('n02531234'), 'missing trailing letter should fail');
assert_true(!bushisa_student_id_is_valid('x02531234f'), 'wrong prefix should fail');
assert_true(!bushisa_student_email_is_valid('other@students.nust.ac.zw', 'n02531234f'), 'email local part must match student number');
assert_true(bushisa_student_email_is_valid('n02531234f@students.nust.ac.zw', 'n02531234f'), 'approved student email should pass');

$adult = (new DateTimeImmutable('today'))->modify('-18 years')->format('Y-m-d');
$underage = (new DateTimeImmutable('today'))->modify('-17 years')->format('Y-m-d');
assert_true(verify_age($adult)['is_valid'] === true, '18th birthday should pass');
assert_true(verify_age($underage)['is_valid'] === false, 'underage date should fail');

fwrite(STDOUT, "unit_signup_verification PASS\n");
