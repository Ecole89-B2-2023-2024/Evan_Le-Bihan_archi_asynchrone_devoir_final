<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Article;
use App\Repository\UserRepository;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ApiController extends AbstractController
{
    #[Route('/api/new_article', name: 'app_api_article_new', methods: ['POST'])]
    public function createPost(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $body = $request->getContent();
        $article = $serializer->deserialize($body, Article::class, 'json');
        $user = $this->getUser();

        $article->setAuteur($user);

        $error = $validator->validate($article);

        $em->persist($article);
        $em->flush();

        return $this->json([
            'status' => 201,
            'message' => 'La ressource a été créée',
            'data' => $article
        ], 201, [], ['groups' => 'article']);
    }

    #[Route('/api/articles', name: 'app_api_article_index')]
    public function index(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findAll();
        return $this->json([
            'status' => 200,
            'message' => 'La ressource a été trouvée',
            'data' => $articles
        ], 200, [], ['groups' => 'article']);
    }

    #[Route('/api/new_user', name: 'app_api_user_new', methods: ['POST'])]
    public function createUser(Request $request, UserPasswordHasherInterface $userPasswordHasher, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $userData = json_decode($request->getContent(), true);

        $user = new User();
        $user->setEmail($userData['email']);
        
        if(isset($userData['password'])){
            $user->setPassword($userData['password']);
        } else {

            return $this->json([
                'status' => 400,
                'message' => 'Le mot de passe est obligatoire'
            ], 400);
        }

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return $this->json($errors, 400);
        }

        $user->setPassword($userPasswordHasher->hashPassword($user, $user->getPassword()));

        $em->persist($user);
        $em->flush();

        return $this->json([
            'status' => 201,
            'message' => 'L\'utilisateur a été créé avec succès.',
            'data' => $user
        ], 201);
    }
    
    #[Route('/api/users', name: 'api_users_list', methods: ['GET'])]
    public function listUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->json([
            'status' => 200,
            'message' => 'Tous les utilisateurs ont été récupérés',
            'data' => $users
        ], 200, [], ['groups' => 'user']);
    }

    #[Route('/api/user/{id}', name: 'app_api_user_get')]
    public function userId(int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json([
                'status' => 404,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        return $this->json([
            'status' => 200,
            'message' => 'Utilisateur trouvé',
            'data' => $user
        ], 200, [], ['groups' => 'user']);
    }
    
    #[Route('/api/article/{id}', name: 'app_api_article_search_by_id')]
    public function articleId(ArticleRepository $articleRepository, int $id): Response
    {
        $article = $articleRepository->find($id);

        if (!$article) {
            return $this->json([
                'status' => 404,
                'message' => 'Article not found',
            ], 404);
        }

        return $this->json([
            'status' => 200,
            'message' => 'Article found',
            'data' => $article
        ], 200, [], ['groups' => 'article']);
    }
}

