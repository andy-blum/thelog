import fetch from 'isomorphic-fetch';
import writeJsonFile from 'write-json-file';
import { filesDir } from './filesDir.js';

const espn = 'https://fantasy.espn.com/apis/v3/games/ffl/seasons/';

export const getLeagueSeason = async () => {
  const now = Date.now();

  const data = await fetch(espn)
    .then(resp => resp.json())
    .catch(err => {
      console.dir(err.message);
      return false;
    });

  if (data) {
    for (const leagueYear of data) {
      if (now > leagueYear.startDate && now < leagueYear.endDate) {
        await writeJsonFile(`${filesDir}/LeagueYear.json`, leagueYear);
        return leagueYear;
      }
    }
  }
}
