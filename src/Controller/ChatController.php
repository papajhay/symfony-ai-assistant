<?php

namespace App\Controller;

use App\Agent\ChatService;
use App\Entity\Conversation;
use App\Service\ConversationNotFoundException;
use App\Service\ConversationService;
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
        ConversationService $conversationService,
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

        $conversationId = $data->conversationId ?? null;
        if ($conversationId !== null) {
            if (is_string($conversationId) && ctype_digit($conversationId)) {
                $conversationId = filter_var($conversationId, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ]);
            } elseif (!is_int($conversationId) || $conversationId < 1) {
                $conversationId = false;
            }

            if ($conversationId === false || $conversationId === null || $conversationId < 1) {
                return new JsonResponse(['error' => 'L’identifiant de conversation est invalide.'], 400);
            }
        }

        try {
            $conversation = $conversationService->addUserMessage($conversationId, $question);
        } catch (ConversationNotFoundException) {
            return new JsonResponse(['error' => 'Conversation introuvable.'], 404);
        }

        try {
            $history = $conversationService->getMessages($conversation);
            array_pop($history); // The current question is passed separately.
            $answer = $chatService->ask($question, $history);
            $conversationService->addAssistantMessage($conversation, $answer);

            return new JsonResponse([
                'conversationId' => $conversation->getId(),
                'question' => $question,
                'answer' => $answer,
                'response' => $answer,
            ]);
        } catch (\Throwable $exception) {
            $logger->error('AI chat request failed: '.$exception->getMessage(), [
                'exception_class' => $exception::class,
                'exception' => $exception,
            ]);

            return new JsonResponse([
                'error' => 'Une erreur est survenue lors de la génération de la réponse.',
                'conversationId' => $conversation->getId(),
            ], 500);
        }
    }

    #[Route('/chat/conversations', name: 'app_chat_conversations', methods: ['GET'])]
    public function conversations(ConversationService $service): JsonResponse
    {
        return new JsonResponse(['conversations' => array_map(static fn (Conversation $conversation): array => [
            'id' => $conversation->getId(),
            'title' => $conversation->getTitle(),
            'createdAt' => $conversation->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $conversation->getUpdatedAt()->format(DATE_ATOM),
        ], $service->list())]);
    }

    #[Route('/chat/conversations/{id}', name: 'app_chat_conversation_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function showConversation(int $id, ConversationService $service): JsonResponse
    {
        $conversation = $service->find($id);
        if (!$conversation) {
            return new JsonResponse(['error' => 'Conversation introuvable.'], 404);
        }

        return new JsonResponse([
            'id' => $conversation->getId(),
            'title' => $conversation->getTitle(),
            'messages' => array_map(static fn ($message): array => [
                'role' => $message->getRole(),
                'content' => $message->getContent(),
                'createdAt' => $message->getCreatedAt()->format(DATE_ATOM),
            ], $service->getMessages($conversation)),
        ]);
    }

    #[Route('/chat/conversations/{id}', name: 'app_chat_conversation_delete', requirements: ['id' => '\\d+'], methods: ['DELETE'])]
    public function deleteConversation(int $id, ConversationService $service): JsonResponse
    {
        $conversation = $service->find($id);
        if (!$conversation) {
            return new JsonResponse(['error' => 'Conversation introuvable.'], 404);
        }
        $service->delete($conversation);
        return new JsonResponse(['deleted' => true]);
    }
}
