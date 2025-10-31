<?php

declare(strict_types=1);

namespace Tourze\MgmCoreBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\MgmCoreBundle\Entity\IdempotencyKey;
use Tourze\MgmCoreBundle\Repository\IdempotencyKeyRepository;

class IdempotencyService
{
    public function __construct(
        private IdempotencyKeyRepository $repository,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    public function getOrStore(string $key, string $scope, callable $operation): mixed
    {
        $existing = $this->repository->findByKeyAndScope($key, $scope);

        if (null !== $existing) {
            return $existing->getResultJson();
        }

        $result = $operation();

        $idempotencyKey = new IdempotencyKey();
        $idempotencyKey->setKey($key);
        $idempotencyKey->setScope($scope);
        $idempotencyKey->setResultJson(is_array($result) ? $result : ['result' => $result]);
        $idempotencyKey->setCreateTime($this->clock->now());

        $this->entityManager->persist($idempotencyKey);
        $this->entityManager->flush();

        return $result;
    }
}
