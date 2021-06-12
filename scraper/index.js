import ESPN from 'espn-fantasy-football-api/node-dev.js';
import { getPlayers } from './src/getPlayers.js';
import { getTeams } from './src/getTeams.js';
import { getLeagueSeason } from './src/getLeagueSeason.js';

const { LEAGUE_ID, ESPN_S2, SWID } = process.env;

const { Client } = ESPN
const client = new Client({
  leagueId: LEAGUE_ID,
  espnS2: ESPN_S2,
  SWID: SWID
});


console.log('Fetching League Season Info.');
const leagueYear = await getLeagueSeason();

console.log('Fetching Players.');
await getPlayers(client, leagueYear);

console.log('Fetching Teams.');
await getTeams(client, leagueYear);
