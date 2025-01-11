<?php

namespace App\Formatter;

use Symfony\Component\HttpFoundation\Response;

class ApiResponseFormatter
{
    /**
     * @var mixed
     */
    private $data;

    private string $message = 'OK';

    private array $errors = [];

    private int $statusCode = Response::HTTP_OK;

    private array $additionalData = [];

    public function setData(mixed $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function setErrors(array $errors): self
    {
        $this->errors = $errors;
        return $this;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function setAdditionalData(array $additionalData): self
    {
        $this->additionalData = $additionalData;
        return $this;
    }

    public function getResponse(): array
    {
        return [
            'data' => $this->data,
            'message' => $this->message,
            'errors' => $this->errors,
            'statusCode' => $this->statusCode,
            'additionalData' => $this->additionalData
        ];
    }
}
