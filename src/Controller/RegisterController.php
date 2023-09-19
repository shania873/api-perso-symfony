<?php

namespace App\Controller;
use SendGrid;
use SendGrid\Mail\Mail;
use App\Entity\Register;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RegisterController extends AbstractController
{
    #[Route('/registers', name: 'app_register')]
    public function register(ManagerRegistry $doctrine,Request $request): Response
    {
        $entityManager = $doctrine->getManager();
        $firstname = $request->attributes->get('data')->getFirstName();
        $lastname = $request->attributes->get('data')->getLastName();
 
        $userEmail = $request->attributes->get('data')->getEmail();

        $password = $request->attributes->get('data')->getPassword();
        $activationToken = $request->attributes->get('data')->getActivationToken();

        $registrationInfo = new Register();
     
        $registrationInfo->setEmail($userEmail);
        $registrationInfo->setFirstname($firstname);
        $registrationInfo->setLastname($lastname);
        $registrationInfo->setPassword($password);
        $registrationInfo->setActivation(false);
        

        $activationToken = bin2hex(random_bytes(32));

        
        $registrationInfo->setActivationToken($activationToken);
        
        $apiKey = ($this->getParameter('app.sendgridapikey')) ?$this->getParameter('app.sendgridapikey'): getenv('SENDGRID_API_KEY');
        $adminEmail = ($this->getParameter('app.emailadmin')) ?$this->getParameter('app.emailadmin'): getenv('ADMIN_EMAIL');
        $adminSendGridEmail = ($this->getParameter('app.emailadminsendgrid')) ?$this->getParameter('app.emailadminsendgrid'): getenv('EMAIL_SENDGRID');

        $sendgrid = new SendGrid($apiKey);

  
        $email = new Mail();
        $email->setFrom($adminSendGridEmail, 'Administrateur');
        $email->setSubject('Confirmez votre compte');
        $email->addTo($adminEmail, 'Nom du destinataire');
        $email->addContent(
            "text/html",
            'Une personne à voulu s\'inscire ! ' . $firstname . ' ' . $lastname . ' ' . $userEmail . ",<br><br>"
        );

  
        try {
            $response = $sendgrid->send($email);
            
            $entityManager->persist($registrationInfo);
            $entityManager->flush();

            return new Response("Merci de vous être inscrit depuis mon application.<br><br> Si vous êtes une personne à laquelle j'ai postulé, je vais activer votre compte dès que possible. Si vous êtes un spammeur, votre compte sera supprimé dans les 30 jours.<br><br> Cordialement, Caroline<br><br>");
  
        } catch (\Exception $e) {
            return new Response('Nous avons rencontré une erreur lors de l\'inscription, veuillez re-essayer plus tard' . $e->getMessage(), 500);
        }
    }
}
