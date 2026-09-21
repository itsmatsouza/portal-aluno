<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Controllers;

use InvalidArgumentException;
use JsonException;
use Leilabrito\PortalAluno\Core\Response;
use Leilabrito\PortalAluno\Services\HotmartWebhookService;
use Throwable;

class HotmartWebhookController
{
    public function __construct(private HotmartWebhookService $service)
    {
    }

    public function receive(): never
    {
        $secret = $_ENV['HOTMART_HOTTOK'] ?? '';
        if ($secret === '') {
            Response::json(['error' => 'Integração não configurada.'], 503);
        }
        $provided = $_SERVER['HTTP_X_HOTMART_HOTTOK'] ?? '';
        if (!is_string($provided) || !hash_equals($secret, $provided)) {
            Response::json(['error' => 'Não autorizado.'], 401);
        }
        $body = file_get_contents('php://input', false, null, 0, 1048577);
        if ($body === false || strlen($body) > 1048576) {
            Response::json(['error' => 'Corpo excede limite permitido.'], 413);
        }
        try {
            $event = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
            if (!is_array($event)) {
                throw new InvalidArgumentException('Objeto JSON obrigatório.');
            }
            $outcome = $this->service->process($event);
        } catch (JsonException | InvalidArgumentException $error) {
            Response::json(['error' => 'Evento inválido. Confira versão e campos obrigatórios.'], 400);
        } catch (Throwable $error) {
            // Não registra payload, credenciais ou dados pessoais.
            error_log('Hotmart: falha no processamento (' . get_class($error) . ').');
            Response::json(['error' => 'Falha ao processar. Confira produto e banco; reenvie o evento.'], 503);
        }
        Response::json(['status' => $outcome]);
    }
}
