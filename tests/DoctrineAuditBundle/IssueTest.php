<?php

namespace DH\DoctrineAuditBundle\Tests;

use DH\DoctrineAuditBundle\Reader\AuditReader;
use DH\DoctrineAuditBundle\Tests\Fixtures\Core\Inheritance\Bike;
use DH\DoctrineAuditBundle\Tests\Fixtures\Core\Inheritance\Car;
use DH\DoctrineAuditBundle\Tests\Fixtures\Core\Inheritance\Vehicle;
use DH\DoctrineAuditBundle\Tests\Fixtures\Issue37\Locale;
use DH\DoctrineAuditBundle\Tests\Fixtures\Issue37\User;
use DH\DoctrineAuditBundle\Tests\Fixtures\Issue40\CoreCase;
use DH\DoctrineAuditBundle\Tests\Fixtures\Issue40\DieselCase;

/**
 * @internal
 */
final class IssueTest extends BaseTest
{
    /**
     * @var string
     */
    protected $fixturesPath = __DIR__.'/Fixtures';

    public function testAuditingSubclass(): void
    {
        $em = $this->getEntityManager();
        $reader = $this->getReader($this->getAuditConfiguration());

        $car = new Car();
        $car->setLabel('La Ferrari');
        $car->setWheels(4);
        $em->persist($car);
        $em->flush();

        $bike = new Bike();
        $bike->setLabel('ZX10R');
        $bike->setWheels(2);
        $em->persist($bike);
        $em->flush();

        $tryke = new Vehicle();
        $tryke->setLabel('Can-am Spyder');
        $tryke->setWheels(3);
        $em->persist($tryke);
        $em->flush();

        $audits = $reader->getAudits(Vehicle::class);
        static::assertCount(1, $audits, 'results count ok.');

        $audits = $reader->getAudits(Vehicle::class, null, null, null, null, false);
        static::assertCount(3, $audits, 'results count ok.');

        $audits = $reader->getAudits(Car::class);
        static::assertCount(1, $audits, 'results count ok.');

        $audits = $reader->getAudits(Bike::class);
        static::assertCount(1, $audits, 'results count ok.');

        $car->setLabel('Taycan');
        $em->persist($car);
        $em->flush();

        $audits = $reader->getAudits(Vehicle::class);
        static::assertCount(1, $audits, 'results count ok.');

        $audits = $reader->getAudits(Car::class);
        static::assertCount(2, $audits, 'results count ok.');
    }

    public function testIssue40(): void
    {
        $em = $this->getEntityManager();
        $reader = $this->getReader($this->getAuditConfiguration());

        $coreCase = new CoreCase();
        $coreCase->type = 'type1';
        $coreCase->status = 'status1';
        $em->persist($coreCase);
        $em->flush();

        $dieselCase = new DieselCase();
        $dieselCase->coreCase = $coreCase;
        $dieselCase->setName('yo');
        $em->persist($dieselCase);
        $em->flush();

        $audits = $reader->getAudits(CoreCase::class);
        static::assertCount(1, $audits, 'results count ok.');
        static::assertSame(AuditReader::INSERT, $audits[0]->getType(), 'AuditReader::INSERT operation.');

        $audits = $reader->getAudits(DieselCase::class);
        static::assertCount(1, $audits, 'results count ok.');
        static::assertSame(AuditReader::INSERT, $audits[0]->getType(), 'AuditReader::INSERT operation.');
    }

    public function testIssue37(): void
    {
        $em = $this->getEntityManager();
        $reader = $this->getReader($this->getAuditConfiguration());

        $localeFR = new Locale();
        $localeFR
            ->setId('fr_FR')
            ->setName('Français')
        ;
        $em->persist($localeFR);
        $em->flush();

        $localeEN = new Locale();
        $localeEN
            ->setId('en_US')
            ->setName('Français')
        ;
        $em->persist($localeEN);
        $em->flush();

        $audits = $reader->getAudits(Locale::class);
        static::assertCount(2, $audits, 'results count ok.');
        static::assertSame('en_US', $audits[0]->getObjectId(), 'AuditEntry::object_id is a string.');
        static::assertSame('fr_FR', $audits[1]->getObjectId(), 'AuditEntry::object_id is a string.');

        $user1 = new User();
        $user1
            ->setUsername('john.doe')
            ->setLocale($localeFR)
        ;
        $em->persist($user1);
        $em->flush();

        $user2 = new User();
        $user2
            ->setUsername('dark.vador')
            ->setLocale($localeEN)
        ;
        $em->persist($user2);
        $em->flush();

        $audits = $reader->getAudits(User::class);
        static::assertCount(2, $audits, 'results count ok.');
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    protected function setUp(): void
    {
        $this->getEntityManager();
        $this->getSchemaTool();

        $configuration = $this->getAuditConfiguration();
        $configuration->setEntities([
            DieselCase::class => ['enabled' => true],
            CoreCase::class => ['enabled' => true],
            Locale::class => ['enabled' => true],
            User::class => ['enabled' => true],
            Vehicle::class => ['enabled' => true],
            Car::class => ['enabled' => true],
            Bike::class => ['enabled' => true],
        ]);

        $this->setUpEntitySchema();
        $this->setUpAuditSchema();
    }
}
