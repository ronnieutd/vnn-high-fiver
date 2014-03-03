<?php

namespace Ronnieutd\FiverBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class DefaultController
 * @package Ronnieutd\FiverBundle\Controller
 *
 * Controller for High Five app
 */
class DefaultController extends Controller
{
    /**
     * Home page view. Displays top 5 players report.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        /* @var \Ronnieutd\FiverBundle\Services\Varvee $varvee */
        $varvee = $this->get('varveeService');

        // get top five players form varvee service
        $topFivePlayers = $varvee
            ->setStateId($this->container->getParameter('varvee.stateId')) // 54
            ->setSportId($this->container->getParameter('varvee.sportId')) // 27
            ->getTopFivePlayers();

        // render template using returned data
        return $this->render(
            'RonnieutdFiverBundle:Default:index.html.twig',
            array(
                'topFivePlayers' => $topFivePlayers,
                'sportId' => $varvee->getSportId()
            )
        );
    }

    /**
     * Player detail view.  Uses sport and player ids to generate right URL
     * and also display game stats, and game chart.
     *
     * @param $sportId
     * @param $playerId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function playerAction($sportId, $playerId) {

        /* @var \Ronnieutd\FiverBundle\Services\Varvee $varvee */
        $varvee = $this->get('varveeService');

        $playerInfo = $varvee
            ->setPlayerId($playerId)
            ->setSportId($sportId)
            ->getPlayerInfo();

        return $this->render(
            'RonnieutdFiverBundle:Default:player.html.twig',
            array(
                'playerInfo' => $playerInfo,
            )
        );
    }

    /**
     * Testing Methodology Page
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function testingAction()
    {
        return $this->render('RonnieutdFiverBundle:Default:testing.html.twig');
    }
}
