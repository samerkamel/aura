<?php

namespace Modules\LetterGenerator\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\LetterGenerator\Models\LetterTemplate;
use App\Models\User;
use Tests\TestCase;

/**
 * Letter Template Feature Test
 *
 * Tests the letter template management functionality including creation,
 * validation, editing, and deletion of letter templates.
 *
 * @author Dev Agent
 */
class LetterTemplateTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    /**
     * Test data constants
     */
    private const TEST_TEMPLATE_NAME = 'Employment Contract';

    private const TEST_TEMPLATE_NAME_AR = 'عقد العمل';

    private const TEST_CONTENT_EN = '<p>Dear {{employee_name}},</p><p>Welcome to our company! Your position is {{employee_position}} with a salary of {{base_salary}}.</p>';

    private const TEST_CONTENT_AR = '<p dir="rtl">عزيزي {{employee_name}}،</p><p dir="rtl">مرحباً بك في شركتنا! منصبك هو {{employee_position}} براتب {{base_salary}}.</p>';

    private const TEST_UPDATED_NAME = 'Updated Contract Template';

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user for authentication
        $this->user = User::factory()->create([
            'email' => 'test@qflow.com',
            'name' => 'Test User'
        ]);
    }

    /**
     * Test that the letter templates index page can be accessed.
     */
    public function test_can_access_letter_templates_index_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('letter-templates.index'));

        $response->assertStatus(200);
        $response->assertViewIs('lettergenerator::templates.index');
    }

    /**
     * Test that the letter template creation page can be accessed.
     */
    public function test_can_access_letter_template_create_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('letter-templates.create'));

        $response->assertStatus(200);
        $response->assertViewIs('lettergenerator::templates.create');
        $response->assertViewHas('placeholders');
    }

    /**
     * Test successful letter template creation with valid English data.
     */
    public function test_can_create_english_letter_template_with_valid_data(): void
    {
        $templateData = [
            'name' => self::TEST_TEMPLATE_NAME,
            'language' => 'en',
            'content' => self::TEST_CONTENT_EN,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('letter-templates.store'), $templateData);

        $response->assertRedirect(route('letter-templates.index'));
        $response->assertSessionHas('success', 'Letter template created successfully.');

        $this->assertDatabaseHas('letter_templates', [
            'name' => self::TEST_TEMPLATE_NAME,
            'language' => 'en',
            'content' => self::TEST_CONTENT_EN,
        ]);
    }

    /**
     * Test successful letter template creation with valid Arabic data.
     */
    public function test_can_create_arabic_letter_template_with_valid_data(): void
    {
        $templateData = [
            'name' => self::TEST_TEMPLATE_NAME_AR,
            'language' => 'ar',
            'content' => self::TEST_CONTENT_AR,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('letter-templates.store'), $templateData);

        $response->assertRedirect(route('letter-templates.index'));
        $response->assertSessionHas('success', 'Letter template created successfully.');

        $this->assertDatabaseHas('letter_templates', [
            'name' => self::TEST_TEMPLATE_NAME_AR,
            'language' => 'ar',
            'content' => self::TEST_CONTENT_AR,
        ]);
    }

    /**
     * Test letter template creation fails with missing required fields.
     */
    public function test_letter_template_creation_fails_with_missing_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('letter-templates.store'), []);

        $response->assertSessionHasErrors(['name', 'language', 'content']);
    }

    /**
     * Test letter template creation fails with invalid language.
     */
    public function test_letter_template_creation_fails_with_invalid_language(): void
    {
        $templateData = [
            'name' => self::TEST_TEMPLATE_NAME,
            'language' => 'invalid',
            'content' => self::TEST_CONTENT_EN,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('letter-templates.store'), $templateData);

        $response->assertSessionHasErrors(['language']);
    }

    /**
     * Test that letter template show page displays correctly.
     */
    public function test_can_view_letter_template(): void
    {
        $template = LetterTemplate::create([
            'name' => self::TEST_TEMPLATE_NAME,
            'language' => 'en',
            'content' => self::TEST_CONTENT_EN,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('letter-templates.show', $template));

        $response->assertStatus(200);
        $response->assertViewIs('lettergenerator::templates.show');
        $response->assertViewHas('letterTemplate', $template);
        $response->assertViewHas('placeholders');
        $response->assertSee($template->name);
        $response->assertSee('English');
    }

    /**
     * Test that letter templates are listed on index page.
     */
    public function test_letter_templates_are_listed_on_index_page(): void
    {
        $template1 = LetterTemplate::create([
            'name' => self::TEST_TEMPLATE_NAME,
            'language' => 'en',
            'content' => self::TEST_CONTENT_EN,
        ]);

        $template2 = LetterTemplate::create([
            'name' => self::TEST_TEMPLATE_NAME_AR,
            'language' => 'ar',
            'content' => self::TEST_CONTENT_AR,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('letter-templates.index'));

        $response->assertStatus(200);
        $response->assertSee($template1->name);
        $response->assertSee($template2->name);
        $response->assertSee('English');
        $response->assertSee('Arabic');
    }

    /**
     * Test that letter template edit page can be accessed.
     */
    public function test_can_access_letter_template_edit_page(): void
    {
        $template = LetterTemplate::create([
            'name' => self::TEST_TEMPLATE_NAME,
            'language' => 'en',
            'content' => self::TEST_CONTENT_EN,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('letter-templates.edit', $template));

        $response->assertStatus(200);
        $response->assertViewIs('lettergenerator::templates.edit');
        $response->assertViewHas('letterTemplate', $template);
        $response->assertViewHas('placeholders');
    }

    /**
     * Test successful letter template update.
     */
    public function test_can_update_letter_template(): void
    {
        $template = LetterTemplate::create([
            'name' => self::TEST_TEMPLATE_NAME,
            'language' => 'en',
            'content' => self::TEST_CONTENT_EN,
        ]);

        $updateData = [
            'name' => self::TEST_UPDATED_NAME,
            'language' => 'ar',
            'content' => self::TEST_CONTENT_AR,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('letter-templates.update', $template), $updateData);

        $response->assertRedirect(route('letter-templates.index'));
        $response->assertSessionHas('success', 'Letter template updated successfully.');

        $this->assertDatabaseHas('letter_templates', [
            'id' => $template->id,
            'name' => self::TEST_UPDATED_NAME,
            'language' => 'ar',
            'content' => self::TEST_CONTENT_AR,
        ]);
    }

    /**
     * Test letter template update fails with invalid data.
     */
    public function test_letter_template_update_fails_with_invalid_data(): void
    {
        $template = LetterTemplate::create([
            'name' => self::TEST_TEMPLATE_NAME,
            'language' => 'en',
            'content' => self::TEST_CONTENT_EN,
        ]);

        $updateData = [
            'name' => '',
            'language' => 'invalid',
            'content' => '',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('letter-templates.update', $template), $updateData);

        $response->assertSessionHasErrors(['name', 'language', 'content']);
    }

    /**
     * Test successful letter template deletion.
     */
    public function test_can_delete_letter_template(): void
    {
        $template = LetterTemplate::create([
            'name' => self::TEST_TEMPLATE_NAME,
            'language' => 'en',
            'content' => self::TEST_CONTENT_EN,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('letter-templates.destroy', $template));

        $response->assertRedirect(route('letter-templates.index'));
        $response->assertSessionHas('success', 'Letter template deleted successfully.');

        $this->assertDatabaseMissing('letter_templates', [
            'id' => $template->id,
        ]);
    }

    /**
     * Test that placeholders are available in template views.
     */
    public function test_placeholders_are_available_in_template_views(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('letter-templates.create'));

        $response->assertSee('{{employee_name}}');
        $response->assertSee('{{employee_position}}');
        $response->assertSee('{{base_salary}}');
        $response->assertSee('{{start_date}}');
        $response->assertSee('{{current_date}}');
    }

    /**
     * Test that Arabic templates display RTL directive correctly.
     */
    public function test_arabic_template_displays_rtl_correctly(): void
    {
        $template = LetterTemplate::create([
            'name' => self::TEST_TEMPLATE_NAME_AR,
            'language' => 'ar',
            'content' => self::TEST_CONTENT_AR,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('letter-templates.show', $template));

        $response->assertSee('dir="rtl"', false);
        $response->assertSee($template->content, false);
    }
}
