<?php

namespace TurboFrame\Http;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $body = '';
    private bool $sent = false;

    private static array $statusTexts = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    public function __construct(string $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public static function make(string $body = '', int $statusCode = 200, array $headers = []): Response
    {
        return new self($body, $statusCode, $headers);
    }

    public static function json(mixed $data, int $statusCode = 200): Response
    {
        $response = new self(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $statusCode);
        $response->header('Content-Type', 'application/json; charset=utf-8');
        return $response;
    }

    public static function html(string $content, int $statusCode = 200): Response
    {
        $response = new self($content, $statusCode);
        $response->header('Content-Type', 'text/html; charset=utf-8');
        return $response;
    }

    public static function redirect(string $url, int $statusCode = 302): Response
    {
        $response = new self('', $statusCode);
        $response->header('Location', $url);
        return $response;
    }

    public static function download(string $filePath, string $filename = null): Response
    {
        if (!file_exists($filePath)) {
            return self::make('File not found', 404);
        }

        $filename = $filename ?? basename($filePath);
        $content = file_get_contents($filePath);
        $mime = mime_content_type($filePath) ?: 'application/octet-stream';

        $response = new self($content, 200);
        $response->header('Content-Type', $mime);
        $response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->header('Content-Length', (string) strlen($content));
        return $response;
    }

    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function headers(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }
        return $this;
    }

    public function cookie(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true
    ): self {
        setcookie($name, $value, $expires, $path, $domain, $secure, $httpOnly);
        return $this;
    }

    public function body(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function append(string $content): self
    {
        $this->body .= $content;
        return $this;
    }

    public function send(): void
    {
        if ($this->sent) {
            return;
        }

        if (!headers_sent()) {
            $statusText = self::$statusTexts[$this->statusCode] ?? 'Unknown Status';
            header("HTTP/1.1 {$this->statusCode} {$statusText}");

            foreach ($this->headers as $name => $value) {
                header("$name: $value");
            }
        }

        // Automatic Minification in Production/Nitrous Mode
        $body = $this->body;
        $isHtml = str_contains($this->headers['Content-Type'] ?? '', 'text/html');
        
        if ($isHtml && file_exists(dirname(dirname(__DIR__)) . '/storage/nitrous/state.php')) {
            $body = preg_replace([
                '/<!--(.|\s)*?-->/', // Remove comments
                '/\s+/',           // Replace multiple spaces with one
                '/(\r?\n)/',        // Remove new lines
            ], [
                '',
                ' ',
                ''
            ], $body);
        }

        echo $body;
        $this->sent = true;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function isSent(): bool
    {
        return $this->sent;
    }
}
