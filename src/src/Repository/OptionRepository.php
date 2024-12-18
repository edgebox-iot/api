<?php

namespace App\Repository;

use App\Entity\Option;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Option|null find($id, $lockMode = null, $lockVersion = null)
 * @method Option|null findOneBy(array $criteria, array $orderBy = null)
 * @method Option[]    findAll()
 * @method Option[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Option::class);
    }

    public function getTunnelStatus(): ?string
    {
        return $this->findOption('TUNNEL_STATUS');
    }

    public function findDomainName(): ?string
    {
        return $this->findOption('DOMAIN_NAME');
    }

    public function findUsername(): ?string
    {
        return $this->findOption('USERNAME');
    }

    public function findCluster(): ?string
    {
        return $this->findOption('CLUSTER');
    }

    public function findShellUrl(): ?string
    {
        return $this->findOption('SHELL_URL');
    }

    public function findShellStatus(): ?string
    {
        return $this->findOption('SHELL_STATUS');
    }

    public function findUpdatesStatus(): ?string
    {
        return $this->findOption('SYSTEM_UPDATES');
    }

    public function findBrowserDevStatus(): ?string
    {
        return $this->findOption('BROWSERDEV_STATUS');
    }

    public function findBrowserDevPassword(): ?string
    {
        return $this->findOption('BROWSERDEV_PASSWORD');
    }

    public function findBrowserDevUrl(): ?string
    {
        return $this->findOption('BROWSERDEV_URL');
    }

    private function findOption(string $name)
    {
        $option = $this->findOneBy(['name' => $name]);

        if (null === $option) {
            return null;
        }

        return $option->getValue();
    }

    // /**
    //  * @return Options[] Returns an array of Options objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Options
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
