import ESPN from 'espn-fantasy-football-api/node-dev.js';
import { getPlayers } from './getPlayers.js';
import { getTeams } from './getTeams.js';
import { getLeagueSeason } from './getLeagueSeason.js';

const { LEAGUE_ID, ESPN_S2, SWID } = process.env;

const { Client } = ESPN
const client = new Client({
  leagueId: LEAGUE_ID,
  espnS2: ESPN_S2,
  SWID: SWID
});

const leagueYear = await getLeagueSeason();
await getPlayers(client, leagueYear);
await getTeams(client, leagueYear);
