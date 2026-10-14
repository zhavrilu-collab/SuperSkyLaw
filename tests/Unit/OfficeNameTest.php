<?php

namespace Tests\Unit;

use App\Enums\OfficeKind;
use App\Support\DirectoryText;
use App\Support\OfficeName;
use PHPUnit\Framework\TestCase;

class OfficeNameTest extends TestCase
{
    public function test_legal_form_words_do_not_make_two_firm_names_different(): void
    {
        $this->assertTrue(OfficeName::matches(
            'Nova tvrtka odvjetničko društvo d.o.o.',
            'Nova tvrtka društvo s ograničenom odgovornošću',
        ));
        $this->assertFalse(OfficeName::matches(
            'Nova tvrtka odvjetničko društvo d.o.o.',
            'Stara tvrtka odvjetničko društvo d.o.o.',
        ));
    }

    public function test_directory_status_keeps_offices_and_skips_trainees(): void
    {
        $this->assertSame(OfficeKind::Sole, OfficeKind::fromDirectoryStatus('Odvjetnica'));
        $this->assertSame(OfficeKind::Joint, OfficeKind::fromDirectoryStatus('Zajednički odvjetnički ured'));
        $this->assertSame(OfficeKind::Firm, OfficeKind::fromDirectoryStatus('Odvjetničko društvo'));
        $this->assertNull(OfficeKind::fromDirectoryStatus('Odvjetnički vježbenik'));
        $this->assertNull(OfficeKind::fromDirectoryStatus('Privremeno ne obavlja odvjetničku službu.'));
    }

    public function test_place_puts_the_postal_code_on_the_city(): void
    {
        $place = DirectoryText::place("Ilica 2\n10000 Zagreb", 'Zagreb');

        $this->assertSame('Ilica 2', $place['address']);
        $this->assertSame('10000 Zagreb', $place['city']);
    }
}
