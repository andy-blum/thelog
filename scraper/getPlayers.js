import writeJsonFile from 'write-json-file';
import { filesDir } from './filesDir.js';

export const getPlayers = async (client, leagueYear) => {
  const fetchedPlayers = await client.getFreeAgents({
    seasonId: leagueYear.id,
    scoringPeriodId: leagueYear.currentScoringPeriod.id
  });

  const players = {}

  for (const { player, rawStats, projectedRawStats } of fetchedPlayers) {

    const positionsToIgnore = [
      'OP',
      'RB/WR',
      'RB/WR/TE',
      'WR/TE',
      'DP',
      'DL',
      'DB',
      'Bench',
      'IR',
    ];

    const filteredPositions = player
      .eligiblePositions
      .filter(pos => (
        !positionsToIgnore.includes(pos) &&
        typeof pos == 'string'
      ));

    player.eligiblePositions = filteredPositions;

    players[player.id] = {
      player,
      rawStats,
      projectedRawStats
    };

    players[player.id].player['rookie'] = true;
  }

  for (let year = (leagueYear.id - 1); year > (leagueYear.id - 3); year--) {
    const previousPlayers = await client.getFreeAgents({
      seasonId: year,
      scoringPeriodId: 0
    });

    for (const { player } of previousPlayers) {
      if (players[player.id]) {
        players[player.id].player['rookie'] = false;
      }
    }
  }

  await writeJsonFile(`${filesDir}/NFLPlayers.json`, players);
}
