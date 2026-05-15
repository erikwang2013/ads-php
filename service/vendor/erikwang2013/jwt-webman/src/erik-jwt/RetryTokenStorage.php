<?php
/*
 * JWT Webman Plugin - JWT authentication for webman framework
 * Copyright (c) 2026 erik
 * Author: erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace ErikJwt;

class RetryTokenStorage implements TokenStorageInterface
{
    private $storage;
    private $maxRetries;
    private $retryDelay;

    public function __construct(
        TokenStorageInterface $storage, 
        int $maxRetries = 3, 
        int $retryDelay = 100
    ) {
        $this->storage = $storage;
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
    }

    public function blacklist(string $jti, int $expireTime): bool
    {
        return $this->retry(function () use ($jti, $expireTime) {
            return $this->storage->blacklist($jti, $expireTime);
        }, 'blacklist');
    }

    public function isBlacklisted(string $jti): bool
    {
        return $this->retry(function () use ($jti) {
            return $this->storage->isBlacklisted($jti);
        }, 'isBlacklisted');
    }

    public function cleanup(): bool
    {
        return $this->retry(function () {
            return $this->storage->cleanup();
        }, 'cleanup');
    }

    private function retry(callable $operation, string $operationName)
    {
        $lastException = null;
        
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return $operation();
            } catch (JWTException $e) {
                $lastException = $e;
                
                // 如果是配置错误，不要重试
                if ($e->getCode() === JWTException::CONFIG_ERROR) {
                    break;
                }
                
                // 最后一次尝试，直接抛出异常
                if ($attempt === $this->maxRetries) {
                    break;
                }
                
                // 等待后重试
                usleep($this->retryDelay * 1000);
            }
        }
        
        throw JWTException::storageError(
            "Operation {$operationName} failed after {$this->maxRetries} attempts: " . 
            $lastException->getMessage()
        );
    }
}