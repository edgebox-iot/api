<?php

namespace App\Controller;

use App\Entity\BlogPost;
use App\Type\BlogPostType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlaygroundController extends AbstractController
{
    /**
     * @Route("/playground", name="playground")
     */
    public function index(Request $request): Response
    {
        $blogPost = new BlogPost();
        $form = $this->createForm(BlogPostType::class, $blogPost);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $task = $form->getData();

            return $this->redirectToRoute('home');
        }

        return $this->render('playground/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
