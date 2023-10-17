<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\DriverManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class UserController extends AbstractController
{
    
    #[Route('/user', name: 'user-list', methods: ['GET'])]
    public function getUsers(EntityManagerInterface $entityManager): Response
    {
        $this->checkDb();
        $users = $entityManager->getRepository(User::class)->findAll();
        return $this->render('user.html.twig', ['users' => $users]);
    }

    #[Route('/user', name: 'user-add', methods: ['POST'])]
    public function postUser(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->checkInput($request);

        // $errors = $validator->validate($user);
        // if (count($errors) > 0) {
        //     return new Response((string) $errors, 400);
        // }
        
        $entityManager->persist($user);
        $entityManager->flush();
        return $this->redirectToRoute('user-list');
    }

    #[Route('/user/delete/{id}', name: 'user-delete', methods: ['GET'])]
    public function deleteUser(int $id, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);
        $entityManager->remove($user);
        $entityManager->flush();
        return $this->redirectToRoute('user-list');
    }

    private function checkInput($request)
    {
        $user = new User();
        $firstname = strip_tags($request->get("firstname"));
        $lastname = strip_tags($request->get("lastname"));
        $address = strip_tags($request->get("address"));
        if (!empty($firstname) || !empty($lastname) || !empty($address)) {
            $data = sprintf("%s - %s - %s", $firstname, $lastname, $address);
        } else {
            $data = "";
        }
        if (!empty($data)) {
            // $user->setId(time());
            $user->setData($data);
        }
        $user->setId(time());
        return $user;
    }

    private function checkDb(){
        $connection = $this->getConnection();
        $tableExists = $this->executeRequest("SELECT * FROM information_schema.tables WHERE table_schema = 'symfony' AND table_name = 'user' LIMIT 1;", $connection);
        if (empty($tableExists)) {
            $this->executeRequest("CREATE TABLE user (id int, data varchar(255))", $connection);
            $this->executeRequest("INSERT INTO user(id, data) values(1, 'Barack - Obama - White House')", $connection);
            $this->executeRequest("INSERT INTO user(id, data) values(1, 'Britney - Spears - America')", $connection);
            $this->executeRequest("INSERT INTO user(id, data) values(1, 'Leonardo - DiCaprio - Titanic')", $connection);
        }
    }
    private function getConnection()
    {
        $connectionParams = [
            'dbname' => 'symfony',
            'user' => 'symfony',
            'password' => '',
            'host' => 'mariadb',
            'driver' => 'pdo_mysql',
        ];
        return DriverManager::getConnection($connectionParams);
    }

    private function executeRequest($sql, $connection)
    {
        $stmt = $connection->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }
    
}