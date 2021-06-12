import writeJsonFile from 'write-json-file';
import { filesDir } from './filesDir.js';

export const getPlayers = async (client, leagueYear) => {

  console.log(`    Fetching league year ${leagueYear.id}, scoring period ${leagueYear.currentScoringPeriod.id}`);
  const fetchedPlayers = await client.getFreeAgents({
    seasonId: leagueYear.id,
    scoringPeriodId: leagueYear.currentScoringPeriod.id
  });
  console.log(`    Found ${fetchedPlayers.length} players.`);

  const players = {}
  const relevantStats = [
    'receivingYards',
    'receivingTouchdowns',
    'receivingReceptions',
    'defensiveSacks',
    'madeFieldGoalsFrom50Plus',
    'madeFieldGoalsFrom40To49',
    'madeFieldGoalsFromUnder40',
    'missedFieldGoals',
    'madeExtraPoints',
    'missedExtraPoints',
    'passingYards',
    'passingTouchdowns',
    'passingInterceptions',
    'rushingYards',
    'rushingTouchdowns',
  ];

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

    for (const statName in projectedRawStats) {
      if (!relevantStats.includes(statName)) {
        delete projectedRawStats[statName]
      } else {
        projectedRawStats[statName] = Math.round(projectedRawStats[statName]);
      }
    }

    for (const statName in rawStats) {
      if (!relevantStats.includes(statName)) {
        delete rawStats[statName]
      } else {
        rawStats[statName] = Math.round(rawStats[statName]);
      }
    }

    players[player.id] = {
      player,
      stats: {
        raw: JSON.stringify(rawStats),
        projected: JSON.stringify(projectedRawStats)
      }
    };

    players[player.id].player['rookie'] = true;
  }


  for (let year = (leagueYear.id - 1); year > 2017; year--) {
    console.log(`    Fetching league year ${year}`);
    const previousPlayers = await client.getFreeAgents({
      seasonId: year,
      scoringPeriodId: 1
    });
    console.log(`    Found ${previousPlayers.length} players.`)

    for (const { player } of previousPlayers) {

      if (players[player.id]) {
        players[player.id].player['rookie'] = false;
      }
    }
  }

  await writeJsonFile(`${filesDir}/NFLPlayers.json`, players);
}
