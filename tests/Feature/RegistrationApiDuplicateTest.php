<?php

namespace Tests\Feature;

use App\Enum\RegistrationStatusEnum;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Edition;
use App\Models\Modality;
use App\Models\Nominee;
use App\Models\Registration;
use App\Notifications\RegistrationProtocol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationApiDuplicateTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    private Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->edition = Edition::create([
            'title' => 'Edicao Teste',
            'regulation' => 'Regulamento',
            'registration_start' => today()->subDays(10),
            'registration_end' => today()->addDays(10),
            'grant_date' => today()->addDays(60),
            'judgment_date' => today()->addDays(30),
            'is_registration_active' => true,
            'applications_per_candidate' => 5,
        ]);

        $this->candidate = Candidate::create($this->candidateFields());
    }

    /**
     * DADO QUE o formulário público envia a mesma inscrição duas vezes (clique duplo)
     * ENTÃO só uma inscrição é criada, a segunda resposta devolve a mesma
     * inscrição e o e-mail de protocolo é enviado uma única vez.
     */
    public function test_envio_duplicado_nao_cria_segunda_inscricao(): void
    {
        $category = $this->createCategory();
        $payload = $this->payload($category, ['title' => 'Projeto Floresta Viva']);

        $first = $this->post('/api/registration', $payload)->assertOk();
        $second = $this->post('/api/registration', $payload)->assertOk();

        $this->assertSame(1, Registration::query()->count());
        $this->assertSame(
            $first->json('data.registration.protocol'),
            $second->json('data.registration.protocol')
        );
        Notification::assertSentOnDemandTimes(RegistrationProtocol::class, 1);
    }

    /**
     * DADO QUE o candidato envia outra inscrição com título diferente
     * ENTÃO ela é criada normalmente.
     */
    public function test_inscricao_com_titulo_diferente_e_criada(): void
    {
        $category = $this->createCategory();

        $this->post('/api/registration', $this->payload($category, ['title' => 'Projeto Um']))->assertOk();
        $this->post('/api/registration', $this->payload($category, ['title' => 'Projeto Dois']))->assertOk();

        $this->assertSame(2, Registration::query()->count());
    }

    /**
     * DADO QUE a inscrição anterior igual foi rejeitada
     * ENTÃO um novo envio cria uma nova inscrição.
     */
    public function test_inscricao_igual_a_uma_rejeitada_e_criada(): void
    {
        $category = $this->createCategory();
        $payload = $this->payload($category, ['title' => 'Projeto Rejeitado']);

        $this->post('/api/registration', $payload)->assertOk();
        Registration::query()->update(['status' => RegistrationStatusEnum::Rejeitado->value]);
        $this->post('/api/registration', $payload)->assertOk();

        $this->assertSame(2, Registration::query()->count());
    }

    /**
     * DADO QUE a indicação honorífica é enviada duas vezes
     * ENTÃO só uma indicação é criada.
     */
    public function test_indicacao_honorifica_duplicada_nao_e_criada(): void
    {
        $category = $this->createCategory(['is_honorific' => true]);
        $payload = $this->payload($category, [
            'name' => 'Pessoa Homenageada',
            'state' => 'AM',
            'contact_data' => 'contato@example.com',
            'presentation' => $this->words(120),
            'activities' => $this->words(120),
            'justification' => $this->words(120),
        ]);

        $this->post('/api/registration', $payload)->assertOk();
        $this->post('/api/registration', $payload)->assertOk();

        $this->assertSame(1, Nominee::query()->count());
    }

    private function createCategory(array $attributes = []): Category
    {
        $modality = Modality::create(['title' => 'Modalidade', 'edition_id' => $this->edition->id]);

        return Category::create(array_merge([
            'modality_id' => $modality->id,
            'title' => 'Categoria',
            'acronym' => 'CAT',
            'recipients_count' => 1,
        ], $attributes));
    }

    /**
     * Payload no formato do formulário público: JSON no campo `data` com ids criptografados.
     *
     * @return array{data: string, category: string}
     */
    private function payload(Category $category, array $inscription): array
    {
        $defaults = $category->is_honorific ? [] : [
            'resumo' => $this->words(120),
            'desenvolvimento' => $this->words(120),
            'objetivo' => $this->words(120),
            'conclusao' => $this->words(120),
        ];

        return [
            'data' => json_encode(array_merge($this->candidateFields(), [
                'candidate_id' => Crypt::encryptString((string) $this->candidate->id),
                'edition' => Crypt::encryptString((string) $this->edition->id),
                'category_id' => Crypt::encryptString((string) $category->id),
            ], $defaults, $inscription)),
            'category' => Crypt::encryptString((string) $category->id),
        ];
    }

    private function candidateFields(): array
    {
        return [
            'nome' => 'Candidata Teste',
            'cpf' => '123.456.789-00',
            'dt_nascimento' => '1990-01-01',
            'rg' => '1234567',
            'rg_expeditor' => 'SSP',
            'rg_uf' => 'AM',
            'sexo' => 'F',
            'cep' => '69000-000',
            'ufendereco' => 'AM',
            'cidade' => 'Manaus',
            'endereco' => 'Rua Teste',
            'numero' => '10',
            'ddd' => '92',
            'celular' => '999999999',
            'email' => 'candidata@example.com',
            'escolaridade' => 'Superior',
            'resumo_curricular' => $this->words(120),
        ];
    }

    private function words(int $count): string
    {
        return trim(str_repeat('palavra ', $count));
    }
}
