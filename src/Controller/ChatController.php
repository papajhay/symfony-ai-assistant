<?php

namespace App\Controller;

use App\Agent\ChatService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ChatController extends AbstractController
{
    #[Route('/chat', name: 'app_chat_ui', methods: ['GET'])]
    public function index(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('chat/index.html.twig');
    }

    #[Route('/chat', name: 'app_chat', methods: ['POST'])]
    public function chat(
        Request $request,
        ChatService $chatService,
        LoggerInterface $logger
    ): JsonResponse {
        try {
            $data = json_decode(
                $request->getContent(),
                false,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $exception) {
            return new JsonResponse([
                'error' => 'Le format JSON est invalide.',
            ], 400);
        }

        if (!is_object($data)) {
            return new JsonResponse([
                'error' => 'Le corps de la requête doit être un objet JSON.',
            ], 400);
        }

        $question = $data->question ?? null;

        if (!is_string($question) || trim($question) === '') {
            return new JsonResponse([
                'error' => 'La question est obligatoire.',
            ], 400);
        }

        try {
            $question = trim($question);
            $answer = $chatService->ask($question);

            return new JsonResponse([
                'question' => $question,
                'answer' => $answer,
            ]);
        } catch (\Throwable $exception) {
            $logger->error('AI chat request failed: '.$exception->getMessage(), [
                'exception_class' => $exception::class,
                'exception' => $exception,
            ]);

            return new JsonResponse([
                'error' => 'Une erreur est survenue lors de la génération de la réponse.',
            ], 500);
        }
    }
}