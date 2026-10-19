/** Start the peer only after the first HTTP worker is actually waiting.
 * Simultaneous TCP arrivals can be accepted by one php -S worker and queue,
 * which is not overlap. This does not relax the distinct-worker assertions.
 */
async function overlapAfterArrival(startFirst, startSecond, readParticipants) {
  let firstError;
  const first = startFirst().catch(error => { firstError = error; return null; });
  const deadline = Date.now() + 5000;
  while (readParticipants().length === 0 && !firstError && Date.now() < deadline) {
    await new Promise(resolve => setTimeout(resolve, 20));
  }
  if (firstError) throw firstError;
  if (readParticipants().length !== 1) {
    await first;
    throw new Error('First HTTP worker did not establish a unique barrier arrival.');
  }
  const results = await Promise.all([first, startSecond()]);
  if (firstError) throw firstError;
  return results;
}
module.exports = { overlapAfterArrival };
