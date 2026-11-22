<?php

namespace Tests\Feature;

use Tests\TestCase;

class WorkspaceThemeTest extends TestCase
{
    public function test_authenticated_layout_uses_light_workspace_and_dark_sidebar(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/lpa.blade.php'));

        $this->assertStringContainsString('data-bs-theme="light"', $layout);
        $this->assertStringContainsString('class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark"', $layout);
        $this->assertStringContainsString('DevAlejandre_logo.svg', $layout);
    }
}
