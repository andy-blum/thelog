import fetch from 'isomorphic-fetch';
import writeJsonFile from 'write-json-file';
import { filesDir } from './filesDir.js';

export const getLeagueSeason = async () => {
  const response = await fetch('https://fantasy.espn.com/apis/v3/games/ffl/seasons/');
  const data = await response.json();
  const now = Date.now();
  for (const leagueYear of data) {
    if (now > leagueYear.startDate && now < leagueYear.endDate) {
      await writeJsonFile(`${filesDir}/LeagueYear.json`, leagueYear);
      return leagueYear;
    }
  }
}
