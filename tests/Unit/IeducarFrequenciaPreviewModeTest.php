<?php

namespace Tests\Unit;

use App\Support\Ieducar\IeducarFrequenciaPreviewMode;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
class IeducarFrequenciaPreviewModeTest extends TestCase
{
    public function test_preview_environment_yields_meta_preview_true(): void
    {
        $this->assertTrue(IeducarFrequenciaPreviewMode::resolveMetaPreview('preview', false, false));
    }

    public function test_homolog_environment_yields_meta_preview_false(): void
    {
        $this->assertFalse(IeducarFrequenciaPreviewMode::resolveMetaPreview('homolog', false, false));
    }

    public function test_force_apply_overrides_preview_environment(): void
    {
        $this->assertFalse(IeducarFrequenciaPreviewMode::resolveMetaPreview('preview', false, true));
    }

    public function test_force_preview_overrides_homolog_environment(): void
    {
        $this->assertTrue(IeducarFrequenciaPreviewMode::resolveMetaPreview('homolog', true, false));
    }

    public function test_force_apply_overrides_force_preview(): void
    {
        $this->assertFalse(IeducarFrequenciaPreviewMode::resolveMetaPreview('preview', true, true));
    }

    public function test_null_environment_behaves_like_non_preview(): void
    {
        $this->assertFalse(IeducarFrequenciaPreviewMode::resolveMetaPreview(null, false, false));
    }
}
