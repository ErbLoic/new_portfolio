<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'stages'), ORM\HasLifecycleCallbacks]
class Stage
{
    use Timestamps;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column] public ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(name: 'company_id', nullable: false, onDelete: 'CASCADE')]
    public ?Company $company = null;
    #[ORM\Column(name: 'start_date', type: 'date')] public ?\DateTimeInterface $startDate = null;
    #[ORM\Column(name: 'end_date', type: 'date', nullable: true)] public ?\DateTimeInterface $endDate = null;

    public const TYPES = ['Stage' => 'stage', 'Alternance' => 'alternance', 'Job' => 'job'];

    /** Type d'expérience : stage, alternance ou job. */
    #[ORM\Column(length: 20, options: ['default' => 'stage'])] public string $type = 'stage';
    #[ORM\Column(name: 'is_current', options: ['default' => false])] public bool $isCurrent = false;
    #[ORM\Column] public string $duration = '';
    #[ORM\Column] public string $role = '';
    #[ORM\Column(type: 'text')] public string $description = '';

    /** @var Collection<int, Competence> */
    #[ORM\ManyToMany(targetEntity: Competence::class)]
    #[ORM\JoinTable(name: 'competence_stage')]
    #[ORM\JoinColumn(name: 'stage_id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'competence_id', onDelete: 'CASCADE')]
    public Collection $competences;

    /** Une expérience terminée doit avoir une date de fin. */
    #[\Symfony\Component\Validator\Constraints\Callback]
    public function validateDates(\Symfony\Component\Validator\Context\ExecutionContextInterface $context): void
    {
        if (!$this->isCurrent && null === $this->endDate) {
            $context->buildViolation('Indiquez une date de fin ou cochez « Expérience en cours ».')->atPath('endDate')->addViolation();
        }
        if ($this->isCurrent) {
            $this->endDate = null;
        }
    }

    public function __construct()
    {
        $this->competences = new ArrayCollection();
    }
}
