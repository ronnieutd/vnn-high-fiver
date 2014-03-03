<?php

namespace Ronnieutd\FiverBundle\Services;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\CssSelector\CssSelector;

use Goutte\Client as GoutteClient;

/**
 * Class Varvee
 * @package Ronnieutd\FiverBundle\Services
 *
 * Service to parse the Individual Leaderboards and player profile pages on varvee.com
 */
class Varvee
{
    var $container; // service container via dependency injection

    var $webClient;

    var $stateId;
    var $sportId;
    var $playerId;

    var $urlLeaderboard; // base URL comes from parameters.yml
    var $urlPlayer; // base URL comes from parameters.yml


    /**
     * Constructor for Varvee class.  Sets Goutte PHP object.
     */
    public function __construct()
    {
        $this->webClient = new GoutteClient();
    }

    /**
     * Setter for the Dependency Injection Container
     *
     * @param \Symfony\Component\DependencyInjection\Container $container
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Getter for the Dependency Injection Container
     * @return container
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * Getter for stateId
     * @return int
     */
    public function getStateId()
    {
        return $this->stateId;
    }

    /**
     * Setter for stateId
     * @param null $stateId
     * @return $this
     */
    public function setStateId($stateId = null)
    {
        $this->stateId = (int) $stateId;

        return $this;
    }

    /**
     * Getter for sportsId
     * @return mixed
     */
    public function getSportId()
    {
        return $this->sportId;
    }

    /**
     * Setter for sportsId
     * @param null $sportId
     * @return $this
     */
    public function setSportId($sportId = null)
    {
        $this->sportId = (int) $sportId;

        return $this;
    }

    /**
     * Getter for playerId
     * @return mixed
     */
    public function getPlayerId()
    {
        return $this->playerId;
    }

    /**
     * setter forplayerId
     * @param null $playerId
     * @return $this
     */
    public function setPlayerId($playerId = null)
    {
        $this->playerId = (int) $playerId;

        return $this;
    }

    /**
     * Retrieves sorted list of players from varvee.com Individual Leaderboards
     * (builds URL dynamically) and scrapes all the player info and displays
     * top 5 players.
     * @return array
     */
    public function getTopFivePlayers()
    {
        // Browse to the Leaderboard page
        $url = $this->getVarveeLeaderboardUrl();
        $crawler = $this->webClient->request('GET', $url);

        $response = $this->webClient->getResponse();

        // we should get a 200 response code
        $status_code = $response->getStatus();
        if (200 != $status_code) {
            echo "The request was not delivered properly by the remote server.";
            exit;
        }

        // CSS Selectors that retrieve the rows in the leaderboard table
        $cssSelector = 'div.table-body tr.odd,tr.even';

        // process each table row (TR)
        $tableData = $crawler->filter($cssSelector)->each(function (Crawler $row, $i) {

            $rowTempData = array();
            $rowResults = array();

            // loop through each row's TDs
            $row->filter('td')->each(function (Crawler $td, $tdPosition) use (&$rowTempData) {

                // If we're in the right TD (7th field), grab the Points per Game (PPG) for ranking
                if (7 == $tdPosition) {
                    $rowTempData['score'] = $td->text();
                }

                // some TDs have links, save link info in keyed pair using td position
                if ($td->filter('a')->count()) {
                    $href = $td->filter('a')->attr('href');
                    $text = $td->filter('a')->text();

                    // makes the link data available when we exit the each()... scope
                    $rowTempData['links'][$tdPosition] = array($text => $href);
                }
            });

            // We have TableData now. Lets build the final result set that we
            // will need, including the text and link URLs from the row TDs

            $playerName = null;
            $playerId = null;

            // get player name and player detail link out of link from td#3
            foreach($rowTempData['links'][3] as $player => $link) {

                $playerName = $player;
                $playerId = array_pop(explode('/', $link));
            }

            // final result is player info, score, array of fields, and array of links
            $returnArray = array(
                'playerName' => $playerName,
                'playerId' => $playerId,

                'score' => $rowTempData['score'],

                'fields' => $row->filter('td')->extract(array('_text')),
                'links' => $rowTempData['links']
            );

            return $returnArray;
        });

        // you can debug this data if you want, or use xdebug if you have it installed
        // echo "<pre>";
        // print_r($tableData);


        // Now that we have all the $tableData, lets trim it to
        // the top X players based on points per game.
        return $this->trimTopPlayersByPPG($tableData);
    }

    /**
     * Takes the tableData from web scrape of leaderboard and uses
     * the scores and sorting to generate the list of 5 top players (or more if ties)
     *
     * @param array $tableData
     * @return array
     */
    private function trimTopPlayersByPPG($tableData = array()) {

        $aryResults = array();

        // Only keep the 5 five scores, but include both users if there is a tie in PPG

        $count = 0; // track when we give up one of our 5 'slots'

        $previousScore = 0; // Holds last player's score so we can check for tie

        $rank = 0; // controls ranks as we hand the out

        $maxRanking = 5; // Top ___ players


        // grab player rows until we hit max count, and properly handle PPG tied scores
        foreach ($tableData as $row) {

            $playerName = null;
            $playerId = null;

            // Get player info from row td#3 link and text) for our result set
            foreach ($row['links'][3] as $name => $link) {
                $playerName = $name;
                $playerId = array_pop(explode('/', $link));
            }

            // Break out if over 5 ranks used and its not another tie in score row
            if ($count >= 5 && $previousScore != $row['score']) break;

            // if score is not a tie, and we didn't break, then we are
            // giving out a new rank.  Update count, rank, and save PPG score.
            if ($previousScore != $row['score']) {
                $count++;
                $rank++;
                $previousScore = $row['score'];
            }

            // push all of this data into $aryResults to return to getTopFivePlayers()
            array_push($aryResults, array(
                'rank' => $rank,
                'score' => $row['score'],
                'name' => $playerName,
                'playerId' => $playerId,
                'sportId' => $this->getSportId()
            ));
        }

    return $aryResults;
    }

    /**
     * Retrieves player's details page from varvee.com (builds URL dynamically)
     * and scrapes all the player info and game stats (Points, team points, W/L)
     * so we can display a details page with game stats chart.
     *
     * @return array
     */
    public function getPlayerInfo()
    {
        // Browse to the detail page
        $url = $this->getVarveePlayerUrl();
        $crawler = $this->webClient->request('GET', $url);

        $response = $this->webClient->getResponse();

        // we should get a 200 response code
        $status_code = $response->getStatus();
        if (200 != $status_code) {
            echo "The request was not delivered properly by the remote server.";
            exit;
        }

        // grab the player details found on the scraped page
        $playerName = trim($crawler->filter('div.profile-details > div.profile-name')->text());

        $playerSchool = trim($crawler->filter('div.detail')->eq(0)->text());
        $playerLocation = trim($crawler->filter('div.detail')->eq(1)->text());

        // list of CSS selectors to reference the game summary table
        $cssSelector = 'div.bottom-column div.table-body tbody tr.odd,tr.even';

        // process each table row (tr)
        $tableData = $crawler->filter($cssSelector)->each(function (Crawler $row, $i) {

            $rowTempData = array();

            // and loop through each TR in that TR
            // we need to grab the W/L data and do some processing
            $row->filter('td')->each(function (Crawler $td, $tdPosition) use (&$rowTempData) {

                // get Win/Loss (first character) if we're in the right TD (3rd field)
                if (2 == $tdPosition) {
                    $rowTempData['outcome'] = substr($td->text(), 0, 1);

                    // figure out which score is winning team and which is losing team
                    list($win, $lose) = preg_split("/-/", substr($td->text(), 1), 2);

                    // If they won, take the winning score, otherwise losing score
                    $rowTempData['score'] = ('W' == $rowTempData['outcome'] ? $win : $lose);
                }
            });

            // extracts all text values from TD's, so its easy to display any field later.
            $fields = $row->filter('td')->extract(array('_text'));

            // return results for $tabledata->filter()->each() above
            $returnArray = array(
                'points' => $fields[4],
                'teamPoints' => $rowTempData['score'],
                'outcome' => $rowTempData['outcome'],
                'opponent' => $fields[1],
                'fields' => $fields
            );
            return $returnArray;
        });

        // echo "<pre>";
        // print_r($tableData);

        // returned array of data to controller
        return array(
            'playerName' => $playerName,
            'playerId' => $this->getPlayerId(),
            'playerSchool' => $playerSchool,
            'playerLocation' => $playerLocation,
            'sportId' => $this->getSportId(),

            'games' => $tableData
        );
    }

    /**
     * Wrapper for Varvee URL to get leaderboard URL
     * @return bool|string
     */
    private function getVarveeLeaderboardUrl()
    {
        return $this->getVarveeUrl('leaderboard');
    }

    /**
     * Wrapper for Varvee URL to get player URL
     * @return bool|string
     */
    private function getVarveePlayerUrl()
    {
        return $this->getVarveeUrl('player');
    }

    /**
     * Internal function to get leaderboard or player URLs
     * @param string $type
     * @return bool|string
     */
    private function getVarveeUrl($type = 'leaderboard')
    {
        if (is_null($type)) {
            return false;
        }

        // use varvee.url.* from parameters in config.yml
        $varveeBasePath = $this->getContainer()->getParameter('varvee.url.' . strtolower($type));
        $varveeTypePath = null;

        // build rest of path depending on which type of Varvee URL we will use.
        switch (strtolower($type)) {
            case 'leaderboard':
                $varveeTypePath = '/' . $this->getStateId() . '/' . $this->getSportId();
                $varveeTypePath .= '/sort:PointsPerGame/direction:desc'; // sort by PPG desc
                break;
            case 'player':
                $varveeTypePath = '/' . $this->getSportId() . '/' . $this->getPlayerId();
                $varveeTypePath .= '/sort:Start/direction:asc';
                break;
        }

        // return final Varvee URL for selected type
        return $varveeBasePath . $varveeTypePath;
    }
}