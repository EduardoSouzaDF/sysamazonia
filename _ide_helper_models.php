<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $protocol
 * @property string $token
 * @property string $action
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $activated_at
 * @property \Illuminate\Support\Carbon|null $consumed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionToken query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionToken valid()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionToken whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionToken whereActivatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionToken whereConsumedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionToken whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionToken whereProtocol($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionToken whereUpdatedAt($value)
 */
	class ActionToken extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $nome
 * @property string $cpf
 * @property \Illuminate\Support\Carbon $dt_nascimento
 * @property string $rg
 * @property string $rg_expeditor
 * @property string $rg_uf
 * @property string $sexo
 * @property string $cep
 * @property string $ufendereco
 * @property string $cidade
 * @property string $endereco
 * @property string $numero
 * @property string|null $complemento
 * @property string $ddd
 * @property string $celular
 * @property bool $whatsapp
 * @property string $email
 * @property string|null $instituicao
 * @property string|null $escolaridade
 * @property string|null $instagram
 * @property string|null $facebook
 * @property string|null $outra_rede_social
 * @property string $resumo_curricular
 * @property-read int $age
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Nominee> $nominees
 * @property-read int|null $nominees_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Registration> $registrations
 * @property-read int|null $registrations_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate escolaridade($level)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate searchByName($name)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCelular($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCidade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereComplemento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCpf($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereDdd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereDtNascimento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereEndereco($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereEscolaridade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereFacebook($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereInstagram($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereInstituicao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereNome($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereNumero($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereOutraRedeSocial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereResumoCurricular($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereRg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereRgExpeditor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereRgUf($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereSexo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereUfendereco($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereWhatsapp($value)
 */
	class Candidate extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $modality_id
 * @property string $title
 * @property string $acronym
 * @property string|null $description
 * @property bool $is_honorific
 * @property int $nominations_count
 * @property int $evaluations_count
 * @property int $recipients_count
 * @property int $submissions_per_candidate
 * @property \Illuminate\Support\Carbon|null $judging_start
 * @property \Illuminate\Support\Carbon|null $judging_end
 * @property bool $is_open_for_submissions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Commission> $commissions
 * @property-read int|null $commissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EvaluationCriterion> $evaluationCriteria
 * @property-read int|null $evaluation_criteria_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $evaluators
 * @property-read int|null $evaluators_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $indicators
 * @property-read int|null $indicators_count
 * @property-read \App\Models\Modality $modality
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Nominee> $nominees
 * @property-read int|null $nominees_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Registration> $registrations
 * @property-read int|null $registrations_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereAcronym($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereEvaluationsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereIsHonorific($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereIsOpenForSubmissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereJudgingEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereJudgingStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereModalityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereNominationsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereRecipientsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereSubmissionsPerCandidate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereUpdatedAt($value)
 */
	class Category extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int|null $category_id
 * @property bool $is_organizing
 * @property bool $is_evaluating
 * @property bool $is_nominating
 * @property bool $is_judging
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Category|null $category
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereIsEvaluating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereIsJudging($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereIsNominating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereIsOrganizing($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereUpdatedAt($value)
 */
	class Commission extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string $regulation
 * @property string $regulation_file_path
 * @property \Illuminate\Support\Carbon $registration_start
 * @property \Illuminate\Support\Carbon $registration_end
 * @property \Illuminate\Support\Carbon $grant_date
 * @property \Illuminate\Support\Carbon $judgment_date
 * @property bool $is_registration_active
 * @property int $applications_per_candidate
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Modality> $modalities
 * @property-read int|null $modalities_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edition active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edition query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edition whereApplicationsPerCandidate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edition whereGrantDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edition whereIsRegistrationActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edition whereJudgmentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edition whereRegistrationEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edition whereRegistrationStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edition whereRegulation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edition whereRegulationFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edition whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Edition whereUpdatedAt($value)
 */
	class Edition extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string|null $description
 * @property numeric $weight
 * @property numeric $min_score
 * @property numeric $max_score
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Category $category
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EvaluationCriterion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EvaluationCriterion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EvaluationCriterion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EvaluationCriterion whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EvaluationCriterion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EvaluationCriterion whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EvaluationCriterion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EvaluationCriterion whereMaxScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EvaluationCriterion whereMinScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EvaluationCriterion whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EvaluationCriterion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EvaluationCriterion whereWeight($value)
 */
	class EvaluationCriterion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property int $candidacy_limit_per_modality
 * @property bool $is_active Indica se a modalidade está ativa
 * @property int $edition_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Category> $categories
 * @property-read int|null $categories_count
 * @property-read \App\Models\Edition $edition
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Modality active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Modality newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Modality newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Modality query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Modality whereCandidacyLimitPerModality($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Modality whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Modality whereEditionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Modality whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Modality whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Modality whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Modality whereUpdatedAt($value)
 */
	class Modality extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $candidate_id
 * @property int $category_id
 * @property string $name
 * @property string $state
 * @property string $contact_data
 * @property string $presentation
 * @property string $activities
 * @property string $justification
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $protocol
 * @property int|null $status
 * @property-read \App\Models\Candidate $candidate
 * @property-read \App\Models\Category $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RegistrationFile> $files
 * @property-read int|null $files_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nominee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nominee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nominee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nominee searchByTitle($title)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nominee status($status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nominee whereActivities($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nominee whereCandidateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nominee whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nominee whereContactData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nominee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nominee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nominee whereJustification($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nominee whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nominee wherePresentation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nominee whereProtocol($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nominee whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nominee whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Nominee whereUpdatedAt($value)
 */
	class Nominee extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\User|null $judge
 * @property-read \App\Models\Registration|null $registration
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Score> $scores
 * @property-read int|null $scores_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Opinion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Opinion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Opinion query()
 */
	class Opinion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $candidate_id
 * @property int $category_id
 * @property string $title
 * @property string|null $coautores
 * @property string $resumo
 * @property string $desenvolvimento
 * @property string $objetivo
 * @property string $conclusao
 * @property \App\Enum\RegistrationStatusEnum $status
 * @property string|null $protocol
 * @property-read \App\Models\Candidate $candidate
 * @property-read \App\Models\Category $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RegistrationFile> $files
 * @property-read int|null $files_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration searchByTitle($title)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration status($status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration whereCandidateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration whereCoautores($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration whereConclusao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration whereDesenvolvimento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration whereObjetivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration whereProtocol($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration whereResumo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Registration whereUpdatedAt($value)
 */
	class Registration extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $registration_id
 * @property string $file_name
 * @property string $file_path
 * @property string|null $file_type
 * @property int|null $file_size
 * @property string|null $description
 * @property string|null $document_type
 * @property int|null $nominee_id
 * @property-read \App\Models\Nominee|null $nominee
 * @property-read \App\Models\Registration|null $registration
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationFile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationFile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationFile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationFile whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationFile whereDocumentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationFile whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationFile whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationFile whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationFile whereFileType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationFile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationFile whereNomineeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationFile whereRegistrationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistrationFile whereUpdatedAt($value)
 */
	class RegistrationFile extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role byName($name)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 */
	class Role extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\EvaluationCriterion|null $evaluationCriterion
 * @property-read \App\Models\Opinion|null $opinion
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Score newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Score newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Score query()
 */
	class Score extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $telefone
 * @property int $is_judge
 * @property int $is_organizer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Category> $evaluatorCategories
 * @property-read int|null $evaluator_categories_count
 * @property-read \App\Models\UserExtraData|null $extraData
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Category> $indicatorCategories
 * @property-read int|null $indicator_categories_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\UserRole|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsJudge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsOrganizer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTelefone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string|null $whatsapp
 * @property string|null $area_atuacao
 * @property string|null $indicado
 * @property string|null $empresa
 * @property string|null $cargo
 * @property string|null $instagram
 * @property string|null $facebook
 * @property string|null $linkedin
 * @property array<array-key, mixed>|null $escolaridade
 * @property string|null $estado
 * @property string|null $cidade
 * @property string|null $cep
 * @property string|null $logradouro
 * @property string|null $complemento
 * @property string|null $unidade
 * @property string|null $bairro
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereAreaAtuacao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereBairro($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereCargo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereCep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereCidade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereComplemento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereEmpresa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereEscolaridade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereFacebook($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereIndicado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereInstagram($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereLinkedin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereLogradouro($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereUnidade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserExtraData whereWhatsapp($value)
 */
	class UserExtraData extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $user_id
 * @property int $role_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Role $role
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRole whereUserId($value)
 */
	class UserRole extends \Eloquent {}
}

