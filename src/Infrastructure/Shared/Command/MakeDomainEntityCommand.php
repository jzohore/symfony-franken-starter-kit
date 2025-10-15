<?php

namespace App\Infrastructure\Shared\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:make:domain-entity',
    description: 'Crée une entité Domain/<Nom>/<Nom>.php, son <Nom>RepositoryInterface, et src/Infrastructure/Repository/<Nom>Repository (Doctrine) qui l\'implémente et utilise DatabaseConnectionService.',
)]
final class MakeDomainEntityCommand extends Command
{
    public function __construct(
        private readonly Filesystem $fs,
        #[Autowire(param: 'kernel.project_dir')] private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('base', null, InputOption::VALUE_REQUIRED, 'Dossier Application (relatif à la racine du projet)', 'src/Application')
            ->addOption('domain-base', null, InputOption::VALUE_REQUIRED, 'Dossier Domain (relatif à la racine du projet)', 'src/Domain')
            ->addOption('entity-class', null, InputOption::VALUE_REQUIRED, 'FQN explicite de l\'entité (si différent de App\\Domain\\<Nom>\\Entity\\<Nom>)')
            ->addOption('repository-class', null, InputOption::VALUE_REQUIRED, 'FQN explicite du RepositoryInterface (si différent de App\\Domain\\<Nom>\\Port\\Out\\<Nom>RepositoryInterface)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Écrase les fichiers existants si nécessaire');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Nom du module
        $rawName = $io->ask('Nom du module (ex: User, Order, BecomeReseller)', null, function (?string $answer) {
            $answer = trim((string) $answer);
            if ($answer === '') {
                throw new \RuntimeException('Le nom du module est requis.');
            }
            return $answer;
        });

        // Respect strict de la casse si déjà Camel/PascalCase saisi
        $studly = self::normalizeStudly($rawName);

        $appBase = trim((string) $input->getOption('base'), '/');              // ex: src/Application
        $domainBase = trim((string) $input->getOption('domain-base'), '/');    // ex: src/Domain
        $entityOverride = $input->getOption('entity-class');                   // FQN explicite
        $repoOverride = $input->getOption('repository-class');                 // FQN explicite
        $force = (bool) $input->getOption('force');

        // FQN Entité
        $entityFqn = $entityOverride
            ? ltrim((string) $entityOverride, '\\')
            : 'App\\Domain\\' . $studly . '\\Entity\\' . $studly;

        // FQN RepositoryInterface
        $repoFqn = $repoOverride
            ? ltrim((string) $repoOverride, '\\')
            : 'App\\Domain\\' . $studly . '\\Port\\Out\\' . $studly . 'RepositoryInterface';

        // Répertoires
        $domainDir        = $this->projectDir . '/' . $domainBase . '/' . $studly;
        $entityDir        = $domainDir . '/Entity';
        $portInDir        = $domainDir . '/Port/In';
        $portOutDir       = $domainDir . '/Port/Out';
        $valueObjectDir   = $domainDir . '/ValueObject';

        $infraRoot        = $this->projectDir . '/src/Infrastructure/' . $studly;
        $infraController  = $infraRoot . '/Controller';
        $infraDoctrine    = $infraRoot . '/Doctrine';
        $infraForm        = $infraRoot . '/Form';
        $infraTwigComp    = $infraRoot . '/Twig/Components';
        $infraValidator   = $infraRoot . '/Validator';
        $infraService   = $infraRoot . '/Service';
        $infraSubscriber   = $infraRoot . '/EventSubscriber';

        $appModuleDir     = $this->projectDir . '/' . $appBase . '/' . $studly;
        $appDtoDir        = $appModuleDir . '/DTO';
        $appUseCaseDir    = $appModuleDir . '/UseCase';

        try {
            // Création des dossiers
            $this->fs->mkdir([
                $entityDir, $portInDir, $portOutDir, $valueObjectDir,
                $infraController, $infraDoctrine, $infraForm, $infraTwigComp, $infraValidator,
                $infraService, $infraSubscriber,
                $appDtoDir, $appUseCaseDir,
            ]);

            // Fichiers Domain
            $entityFile   = $entityDir . '/' . $studly . '.php';
            $repoPortFile = $portOutDir . '/' . $studly . 'RepositoryInterface.php';
            $createIn     = $portInDir . '/Create'  . $studly . 'Interface.php';
            $updateIn     = $portInDir . '/Update'  . $studly . 'Interface.php';
            $deleteIn     = $portInDir . '/Delete'  . $studly . 'Interface.php';
            $restoreIn    = $portInDir . '/Restore' . $studly . 'Interface.php';

            // Fichiers Infrastructure
            $repoImplFile   = $infraDoctrine . '/' . $studly . 'Repository.php';
            $formFile       = $infraForm . '/' . $studly . 'Type.php';
            $controllerFile = $infraController . '/' . $studly . 'Controller.php';
            // Twig/Components et Validator restent vides (gitkeep)
            $twigGitkeep    = $infraTwigComp . '/.gitkeep';
            $validatorKeep  = $infraValidator . '/.gitkeep';
            $controllerKeep = $infraController . '/.gitkeep';

            // Fichiers Application
            $dtoFile      = $appDtoDir . '/' . $studly . 'DTO.php';
            $createUse    = $appUseCaseDir . '/Create'  . $studly . 'UseCase.php';
            $updateUse    = $appUseCaseDir . '/Update'  . $studly . 'UseCase.php';
            $deleteUse    = $appUseCaseDir . '/Delete'  . $studly . 'UseCase.php';
            $restoreUse   = $appUseCaseDir . '/Restore' . $studly . 'UseCase.php';
            $repoInterfaceNs = preg_replace('/\\\\Entity\\\\[^\\\\]+$/', '\\\\Port\\\\Out', $entityFqn);

            // Écriture
            $this->dumpIfAllowed($entityFile,   $this->tplEntity($studly), $force);
            $this->dumpIfAllowed($repoPortFile, $this->tplRepositoryInterface($repoInterfaceNs, $entityFqn, $studly), $force);
            $this->dumpIfAllowed($createIn,     $this->tplPortIn($studly, 'Create'), $force);
            $this->dumpIfAllowed($updateIn,     $this->tplPortIn($studly, 'Update'), $force);
            $this->dumpIfAllowed($deleteIn,     $this->tplPortIn($studly, 'Delete'), $force);
            $this->dumpIfAllowed($restoreIn,    $this->tplPortIn($studly, 'Restore'), $force);

            $this->dumpIfAllowed($repoImplFile, $this->tplDoctrineRepository($studly, $entityFqn, $repoFqn), $force);
            $this->dumpIfAllowed($formFile,     $this->tplFormType($studly, $entityFqn), $force);
            // Controller minimal optionnel (vide par défaut; on met un .gitkeep)
            $this->dumpIfAllowed($controllerKeep, '', $force);
            $this->dumpIfAllowed($twigGitkeep,   '', $force);
            $this->dumpIfAllowed($validatorKeep, '', $force);

            $this->dumpIfAllowed($dtoFile,    $this->tplDto($studly, $entityFqn), $force);
            $this->dumpIfAllowed($createUse,  $this->tplUseCase($studly, $entityFqn, $repoFqn, 'Create'), $force);
            $this->dumpIfAllowed($updateUse,  $this->tplUseCase($studly, $entityFqn, $repoFqn, 'Update'), $force);
            $this->dumpIfAllowed($deleteUse,  $this->tplUseCase($studly, $entityFqn, $repoFqn, 'Delete'), $force);
            $this->dumpIfAllowed($restoreUse, $this->tplUseCase($studly, $entityFqn, $repoFqn, 'Restore'), $force);

        } catch (IOExceptionInterface $e) {
            $io->error('Échec: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->success(sprintf('Module %s généré.', $studly));
        $io->writeln('- Entité: ' . $entityFqn);
        $io->writeln('- Port/Out: ' . $repoFqn);
        $io->writeln(sprintf('- Application: %s/%s', $appBase, $studly));
        $io->writeln(sprintf('- Domain: %s/%s', $domainBase, $studly));
        $io->writeln('- Infrastructure: src/Infrastructure/' . $studly);

        return Command::SUCCESS;
    }

    private function dumpIfAllowed(string $file, string $content, bool $force): void
    {
        if ($this->fs->exists($file) && !$force) {
            return;
        }
        // Crée le fichier même vide (.gitkeep)
        $this->fs->dumpFile($file, $content);
    }

    // ——— TEMPLATES ———

    private function tplEntity(string $studly): string
    {
        $domainNs = 'App\\Domain\\' . $studly;
        $table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $studly)); // BecomeReseller -> become_reseller
        $repositoryFqcn = 'App\\Infrastructure\\' . $studly . '\\Doctrine\\' . $studly . 'Repository';
        $repositoryFqcnT = $studly . 'Repository::class';

        $tpl = <<<'PHP'
<?php

declare(strict_types=1);

namespace {{DOMAIN_NS}}\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use DateTimeImmutable;
use DateTimeZone;
use {{REPOSITORY_FQCN}};
use App\Infrastructure\Shared\Traits\Trashable;

#[ORM\Entity(repositoryClass: {{REPOSITORY_FQCNT}})]
#[ORM\Table(name: '{{TABLE}}')]
final class {{STUDLY}}
{
    use Trashable;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null {
        get => $this->id;
    }

    #[ORM\Column(type: 'datetime_immutable')]
    public DateTimeImmutable $createdAt {
        get => $this->createdAt;
    }

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $updatedAt {
        get => $this->updatedAt;
        set => $this->updatedAt = $value;
    }

    public function __construct()
    {
        $tz = new DateTimeZone('Europe/Paris');
        $this->createdAt = new DateTimeImmutable('now', $tz);
        $this->updatedAt = new DateTimeImmutable('now', $tz);
    }

    // TODO: ajoutez vos champs de domaine ici.
}
PHP;

        return strtr($tpl, [
            '{{STUDLY}}'          => $studly,
            '{{DOMAIN_NS}}'       => $domainNs,
            '{{TABLE}}'           => $table,
            '{{REPOSITORY_FQCN}}' => $repositoryFqcn,
            '{{REPOSITORY_FQCNT}}' => $repositoryFqcnT,
        ]);
    }



    private function tplRepositoryInterface(
        string $repoInterfaceNs,   // ex: 'App\\Domain\\BecomeReseller\\Port\\Out'
        string $entityFqcn,        // ex: 'App\\Domain\\BecomeReseller\\Entity\\BecomeReseller'
        string $studly             // ex: 'BecomeReseller'
    ): string {
        $tpl = <<<'PHP'
<?php

declare(strict_types=1);

namespace {{DOMAIN_NS}};

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Uid\Uuid;
use {{ENTITY_FQCN}};

interface {{STUDLY}}RepositoryInterface
{
    /**
     * @param Uuid $id
     */
    public function getById(Uuid $id): ?{{STUDLY}};

    /**
     * @param {{STUDLY}} $entity
     */
    public function save({{STUDLY}} $entity): void;

    /**
     * @param {{STUDLY}} $entity
     */
    public function delete({{STUDLY}} $entity): void;

    /**
     * @param string|null $querySearch
     * @param array|null $status
     * @param bool|null $isDeleted
     * @return QueryBuilder
     */
    public function search(?string $querySearch = null, ?array $status = [], ?bool $isDeleted = null): QueryBuilder;
}
PHP;

        return strtr($tpl, [
            '{{DOMAIN_NS}}'  => $repoInterfaceNs,
            '{{ENTITY_FQCN}}'=> $entityFqcn,
            '{{STUDLY}}'     => $studly,
        ]);
    }



    private function tplPortIn(string $studly, string $action): string
    {
        $ns = 'App\\Domain\\' . $studly . '\\Port\\In';
        $dtoFqn = 'App\\Application\\' . $studly . '\\DTO\\' . $studly . 'DTO';
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$ns};

use {$dtoFqn};

interface {$action}{$studly}Interface
{
    public function __invoke({$studly}DTO \$dto);
}
PHP;
    }

    private function tplDoctrineRepository(string $studly, string $entityFqcn, string $repoInterfaceFqcn): string
    {
        // Déduit le namespace du dépôt à partir de l'entité:
        // App\Domain\X\Entity\Y -> App\Domain\X\Repository
        $repoNs = preg_replace('/\\\\Entity\\\\[^\\\\]+$/', '\\\\Doctrine', $entityFqcn);

        $tpl = <<<'PHP'
<?php

declare(strict_types=1);
namespace App\Infrastructure\{{STUDLY}}\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use {{ENTITY_FQCN}};
use {{REPO_INTERFACE_FQCN}} as {{STUDLY}}RepositoryInterface;

final class {{STUDLY}}Repository extends ServiceEntityRepository implements {{STUDLY}}RepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, {{STUDLY}}::class);
    }

    public function getById(Uuid $id): ?{{STUDLY}}
    {
        return $this->getEntityManager()->find({{STUDLY}}::class, $id);
    }

    public function save({{STUDLY}} $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function delete({{STUDLY}} $entity): void
    {
        $em = $this->getEntityManager();
        $em->remove($entity);
        $em->flush();
    }

    public function search(?string $querySearch = null, ?array $status = [], ?bool $isDeleted = null): QueryBuilder
    {
        $em = $this->getEntityManager();

        $alias = strtolower((new \ReflectionClass({{STUDLY}}::class))->getShortName());

        $qb = $em->createQueryBuilder()
            ->from({{STUDLY}}::class, $alias)
            ->select($alias)
            ->orderBy($alias . '.createdAt', 'DESC');
            ;

        if ($isDeleted !== null) {
            $qb->andWhere(
                $isDeleted
                    ? $qb->expr()->isNotNull($alias . '.deletedAt')
                    : $qb->expr()->isNull($alias . '.deletedAt')
            );
        }

        if (!empty($status)) {
            $qb->andWhere($alias . '.status IN (:status)')
               ->setParameter('status', array_map(static fn($s) => (string) $s, $status));
        }

        if (!empty($querySearch)) {
            $search = '%' . trim(mb_strtolower($querySearch)) . '%';

            $meta = $em->getClassMetadata({{STUDLY}}::class);
            $stringFields = [];
            foreach ($meta->getFieldNames() as $field) {
                $type = $meta->getTypeOfField($field);
                if (in_array($type, ['string', 'text'], true)) {
                    $stringFields[] = $field;
                }
            }

            if ($stringFields) {
                $orX = $qb->expr()->orX();
                foreach ($stringFields as $field) {
                    $orX->add($qb->expr()->like('LOWER(' . $alias . '.' . $field . ')', ':query'));
                }
                $qb->andWhere($orX)->setParameter('query', $search);
            }
        }

        return $qb;
    }
}
PHP;

        return strtr($tpl, [
            '{{REPO_NS}}'            => $repoNs,
            '{{ENTITY_FQCN}}'        => $entityFqcn,
            '{{REPO_INTERFACE_FQCN}}'=> $repoInterfaceFqcn,
            '{{STUDLY}}'             => $studly,
        ]);
    }



    private function tplFormType(string $studly): string
    {
        $tpl = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Infrastructure\{{STUDLY}}\Form;

use App\Application\{{STUDLY}}\DTO\{{STUDLY}}DTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class {{STUDLY}}Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // TODO: ajouter les champs mappés au DTO (ex: ->add('title'))
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => {{STUDLY}}DTO::class,
        ]);
    }
}
PHP;

        return strtr($tpl, [
            '{{STUDLY}}' => $studly,
        ]);
    }


    private function tplDto(string $studly, string $entityFqn): string
    {
        $ns = 'App\\Application\\' . $studly . '\\DTO';
        $entityShortName = $this->shortClass($entityFqn);

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$ns};

use {$entityFqn};
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Uid\Uuid;
use DateTimeImmutable;

#[Map(target: {$entityShortName}::class)]
final class {$studly}DTO
{
    /**
     * @var Uuid|null
     */
    public ?Uuid \$uuid = null;

    /**
     * @var DateTimeImmutable|null
     */
    public ?DateTimeImmutable \$updatedAt = null;

    /**
     * @var DateTimeImmutable|null
     */
    public ?DateTimeImmutable \$deletedAt = null;

    public static function fromEntity({$entityShortName} \$entity): self
    {
        \$dto = new self();
        \$dto->uuid = \$entity->id;
        \$dto->updatedAt = \$entity->updatedAt;

        return \$dto;
    }
}
PHP;
    }


    private function tplUseCase(string $studly, string $entityFqn, string $repoFqn, string $action): string
    {
        $ns = 'App\\Application\\' . $studly . '\\UseCase';
        $dtoFqn = 'App\\Application\\' . $studly . '\\DTO\\' . $studly . 'DTO';
        $entityShort = $this->shortClass($entityFqn);
        $repoShort = $this->shortClass($repoFqn);
        $ifaceFqn = 'App\\Domain\\' . $studly . '\\Port\\In\\' . $action . $studly . 'Interface';
        $ifaceShort = $this->shortClass($ifaceFqn);
        $var = lcfirst($studly);
        $repoVar = $var . 'Repository';

        if ($action === 'Create') {
            $returnType = $entityShort;
            $body = <<<PHP
        \${$var} = new {$entityShort}();
        \$this->mapper->map(\$dto, \${$var});
        \$this->{$repoVar}->save(\${$var});

        return \${$var};
PHP;
        } elseif ($action === 'Update') {
            $returnType = $entityShort;
            $body = <<<PHP
        /** @var Uuid|null \$id */
        \$id = \$dto->uuid;
        if (!\$id) {
            throw new NotFoundHttpException('{$studly} introuvable');
        }

        \${$var} = \$this->{$repoVar}->getById(\$id);
        if (!\${$var}) {
            throw new NotFoundHttpException('{$studly} introuvable');
        }

        \$this->mapper->map(\$dto, \${$var});
        \$this->{$repoVar}->save(\${$var});

        return \${$var};
PHP;
        } elseif ($action === 'Delete') {
            $returnType = 'void';
            $body = <<<PHP
        /** @var Uuid|null \$id */
        \$id = \$dto->uuid;
        if (!\$id) {
            throw new NotFoundHttpException('{$studly} introuvable');
        }

        \${$var} = \$this->{$repoVar}->getById(\$id);
        if (!\${$var}) {
            throw new NotFoundHttpException('{$studly} introuvable');
        }

        // Marquer comme supprimé côté DTO pour que le mapper propage sur l'entité
        \$dto->deletedAt = now()->setTimeZone(new \\DateTimeZone('Europe/Paris'));

        \$this->mapper->map(\$dto, \${$var});
        \$this->{$repoVar}->save(\${$var});
PHP;
        } else { // Restore
            $returnType = 'void';
            $body = <<<PHP
        /** @var Uuid|null \$id */
        \$id = \$dto->uuid;
        if (!\$id) {
            throw new NotFoundHttpException('{$studly} introuvable');
        }

        \${$var} = \$this->{$repoVar}->getById(\$id);
        if (!\${$var}) {
            throw new NotFoundHttpException('{$studly} introuvable');
        }

        // Annuler la suppression côté DTO pour restauration
        \$dto->deletedAt = null;

        \$this->mapper->map(\$dto, \${$var});
        \$this->{$repoVar}->save(\${$var});
PHP;
        }

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$ns};

use {$dtoFqn};
use {$repoFqn};
use $entityFqn;
use {$ifaceFqn};
use Symfony\\Component\\Uid\\Uuid;
use function Symfony\\Component\\Clock\\now;
use Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;


final readonly class {$action}{$studly}UseCase implements {$ifaceShort}
{
    public function __construct(
        private {$repoShort} \${$repoVar},
        private ObjectMapperInterface \$mapper,
    ) {}

    public function __invoke({$studly}DTO \$dto)
    {
{$this->indent($body, 8)}
    }
}
PHP;
    }



    // ——— Helpers ———

    private static function normalizeStudly(string $value): string
    {
        $value = trim($value);

        // Si déjà Camel/PascalCase avec au moins une majuscule et sans séparateur -> conserver tel quel (et majuscule initiale)
        if (!preg_match('/[-_\s]/', $value) && preg_match('/[A-Z]/', $value)) {
            return ucfirst($value);
        }

        // Sinon, construire en StudlyCase depuis tokens
        $value = preg_replace('/[^A-Za-z0-9]+/', ' ', $value) ?? $value;
        $parts = preg_split('/\s+/', strtolower(trim($value))) ?: [];
        $parts = array_map(static fn(string $p) => ucfirst($p), $parts);
        return implode('', $parts);
    }

    private function shortClass(string $fqn): string
    {
        $fqn = ltrim($fqn, '\\');
        $pos = strrpos($fqn, '\\');
        return $pos === false ? $fqn : substr($fqn, $pos + 1);
    }

    private function indent(string $code, int $spaces): string
    {
        $pad = str_repeat(' ', $spaces);
        return implode("\n", array_map(fn($l) => $pad . rtrim($l), explode("\n", rtrim($code))));
    }
}
