import writeJsonFile from 'write-json-file';
import { filesDir } from './filesDir.js';

export const getTeams = async (client, leagueYear) => {
  const fetchedTeams = await client.getTeamsAtWeek({
    seasonId: leagueYear.id,
    scoringPeriodId: leagueYear.currentScoringPeriod.id
  });

  const teams = [];

  for (const { leagueId, id, abbreviation, name, wins, losses, ties, playoffSeed, finalStandingsPosition } of fetchedTeams) {
    teams.push({
      teamID: `${leagueId}-${id}`,
      name,
      abbr: abbreviation,
      record: `${wins}-${losses}-${ties}`,
      playoffSeed,
      finalStandingsPosition
    });
  }

  await writeJsonFile(`${filesDir}/LOGTeams.json`, teams);
}
