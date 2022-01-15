<?php

namespace App\Controller;

use App\Entity\Series;
use App\Form\SeriesType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/series')]
class SeriesController extends AbstractController
{
    /*#[Route('/', name: 'series_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $series = $entityManager
            ->getRepository(Series::class)
            ->findBy(
                ['yearStart' => [1990,1991,1992,1993,1994,1995]],
                ['yearStart' => 'ASC']
            );
        return $this->render('series/index.html.twig', [
            'series' => $series,
        ]);
    }*/

    #[Route('/', name: 'series_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $titre = $_GET['titre'] ?? "";
        $ADebut = $_GET['An_Debut'] ?? 1899-03-21;
        $AFin = $_GET['An_Fin'] ?? 2023-01-01;
        $page = $_GET['Page'] ?? 0;
        //$triNote = $_Get['triNote'] ?? "Pas coché";

        if($page <= 0 ) { $page = 1;}
        
        $page = $page-1;
        if(isset($_GET['triNote'])){
            $em = $this->getDoctrine()->getManager();
            $repository = $em->getRepository(Rating::class);
            $series = $repository->createQueryBuilder('r')
                //->select('s')
                //->distinct()
                //->from(Rating::class,'r')
                ->innerJoin('r.series', 's','WITH', 's.id = r.series')
                ->where ('s.title like ?1 AND s.yearStart >= ?2 AND 
                   (s.yearEnd <= ?3 OR s.yearEnd IS NULL)')
                ->orderBy('AVG(r.value)','ASC'/*,'s.title','ASC'*/)
                ->setParameter(1,'%'.$titre.'%')
                ->setParameter(2,$ADebut)
                ->setParameter(3,$AFin)
                ->setFirstResult($page*10)
                ->setMaxResults(9)
                ->getQuery()
                ->getResult()
                ;
        } else {
            $em = $this->getDoctrine()->getManager();
            $repository = $em->getRepository(Series::class);
            $series = $repository->createQueryBuilder('s')
                ->where ('s.title like ?1 AND s.yearStart >= ?2 AND 
                   (s.yearEnd <= ?3 OR s.yearEnd IS NULL)')
                ->orderBy('s.title','ASC')
                ->setParameter(1,'%'.$titre.'%')
                ->setParameter(2,$ADebut)
                ->setParameter(3,$AFin)
                ->setFirstResult($page*10)
                ->setMaxResults(9)
                ->getQuery()
                ->getResult()
                ;
        }
        
        $page = $page+1;

        return $this->render('series/index.html.twig', [
            'series' => $series, 
            'titre'=> $titre, 
            'ADebut' => $ADebut,
            'AFin' => $AFin,
            'page' => $page
        ]);
    }


    #[Route('/new', name: 'series_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $series = new Series();
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($series);
            $entityManager->flush();

            return $this->redirectToRoute('series_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('series/new.html.twig', [
            'series' => $series,
            'form' => $form,
        ]);
    }

    /*#[Route('/{id}', name: 'series_show', methods: ['GET'])]
    public function show(Series $series): Response
    {
        return $this->render('series/show.html.twig', [
            'series' => $series,
        ]);
    }*/
    #[Route('/{id}', name: 'series_show', methods: ['GET'])]
    public function show(Series $series): Response
    {
        $em = $this->getDoctrine()->getManager();
        $repositorySai = $em->getRepository(Season::class);

        $saison = $repositorySai->createQueryBuilder('s')
            ->where ('s.series = ?1')
            ->orderBy('s.number', 'ASC')
            ->setParameter(1,$series->getId())
            ->getQuery()
            ->getResult()
            ;
        $repositoryEp = $em->getRepository(Episode::class);
        $episode = $repositoryEp->createQueryBuilder('e')
            ->innerJoin('e.season', 's','WITH', 'e.season = s.id')
            ->where('s.series = ?1')
            ->orderBy('e.number','ASC')
            ->setParameter(1,$series->getId())
            ->getQuery()
            ->getResult()
            ;
        return $this->render('series/show.html.twig', [
            'series' => $series, 'seasons' => $saison, 'episodes' => $episode
        ]);
    }

    #[Route('/poster/{id}', name: 'series_poster_show', methods: ['GET'])]
    public function showPoster(Series $series): Response
    {
        return new Response (stream_get_contents($series->getPoster()),
            200, array('Content-type' => 'image/jpeg',
        ));
    }

    #[Route('/trailer/{id}', name: 'series_trailer_show', methods: ['GET'])]
    public function showTrailer(Series $series) : Response
    {
        $lien_trailer = $series->getYoutubeTrailer();
        $nouv_trailer = substr($lien_trailer,0,24);
        $nouv_trailer = $nouv_trailer."embed";
        $nouv_trailer = $nouv_trailer.substr($lien_trailer,29);
        //$lien_tailer = preg_replace("watch","embed",$lien_trailer);
        return new Response ($nouv_trailer);
    }
    
    /*#[Route('/{id}/edit', name: 'series_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('series_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('series/edit.html.twig', [
            'series' => $series,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'series_delete', methods: ['POST'])]
    public function delete(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$series->getId(), $request->request->get('_token'))) {
            $entityManager->remove($series);
            $entityManager->flush();
        }

        return $this->redirectToRoute('series_index', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/poster/{id}', name: 'series_poster_show', methods: ['GET'])]
    public function showPoster(Series $series): Response
    {
        return new Response (stream_get_contents($series->getPoster()),
            200, array('Content-type' => 'image/jpeg',
        ));
    }*/
}
