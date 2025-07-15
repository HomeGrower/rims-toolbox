<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            ['id' => 1, 'name' => 'Afrikaans', 'code' => 'af'],
            ['id' => 114, 'name' => 'Albanian', 'code' => 'sq'],
            ['id' => 2, 'name' => 'Arabic', 'code' => 'ar'],
            ['id' => 82, 'name' => 'Armenian', 'code' => 'hy'],
            ['id' => 64, 'name' => 'Basque', 'code' => 'eu'],
            ['id' => 21, 'name' => 'Bosnian', 'code' => 'bs'],
            ['id' => 20, 'name' => 'Bulgarian', 'code' => 'bg'],
            ['id' => 19, 'name' => 'Byelorussian', 'code' => 'be'],
            ['id' => 22, 'name' => 'Catalan', 'code' => 'ca'],
            ['id' => 131, 'name' => 'Chinese', 'code' => 'zh'],
            ['id' => 133, 'name' => 'Chinese (HK)', 'code' => 'zh-HK'],
            ['id' => 132, 'name' => 'Chinese (PRC)', 'code' => 'zh-CN'],
            ['id' => 80, 'name' => 'Croatian', 'code' => 'hr'],
            ['id' => 23, 'name' => 'Czech', 'code' => 'cs'],
            ['id' => 24, 'name' => 'Danish', 'code' => 'da'],
            ['id' => 100, 'name' => 'Dutch (Belgium)', 'code' => 'nl-BE'],
            ['id' => 98, 'name' => 'Dutch (Standard)', 'code' => 'nl'],
            ['id' => 32, 'name' => 'English', 'code' => 'en'],
            ['id' => 63, 'name' => 'Estonian', 'code' => 'et'],
            ['id' => 67, 'name' => 'Faeroese', 'code' => 'fo'],
            ['id' => 65, 'name' => 'Farsi', 'code' => 'fa'],
            ['id' => 66, 'name' => 'Finnish', 'code' => 'fi'],
            ['id' => 68, 'name' => 'French', 'code' => 'fr'],
            ['id' => 76, 'name' => 'Gaelic (Irish)', 'code' => 'ga'],
            ['id' => 75, 'name' => 'Gaelic (Scots)', 'code' => 'gd'],
            ['id' => 77, 'name' => 'Galician', 'code' => 'gl'],
            ['id' => 25, 'name' => 'German', 'code' => 'de'],
            ['id' => 31, 'name' => 'Greek', 'code' => 'el'],
            ['id' => 78, 'name' => 'Hebrew', 'code' => 'he'],
            ['id' => 79, 'name' => 'Hindi', 'code' => 'hi'],
            ['id' => 81, 'name' => 'Hungarian', 'code' => 'hu'],
            ['id' => 84, 'name' => 'Icelandic', 'code' => 'is'],
            ['id' => 83, 'name' => 'Indonesian', 'code' => 'id'],
            ['id' => 74, 'name' => 'Irish', 'code' => 'ga'],
            ['id' => 85, 'name' => 'Italian', 'code' => 'it'],
            ['id' => 86, 'name' => 'Italian (Swiss)', 'code' => 'it-CH'],
            ['id' => 87, 'name' => 'Japanese', 'code' => 'ja'],
            ['id' => 89, 'name' => 'Korea (North)', 'code' => 'ko-KP'],
            ['id' => 90, 'name' => 'Korea (South)', 'code' => 'ko-KR'],
            ['id' => 88, 'name' => 'Korean', 'code' => 'ko'],
            ['id' => 93, 'name' => 'Latvian', 'code' => 'lv'],
            ['id' => 92, 'name' => 'Lithuanian', 'code' => 'lt'],
            ['id' => 95, 'name' => 'Macedonian', 'code' => 'mk'],
            ['id' => 96, 'name' => 'Malaysian', 'code' => 'ms'],
            ['id' => 97, 'name' => 'Maltese', 'code' => 'mt'],
            ['id' => 102, 'name' => 'Norwegian', 'code' => 'no'],
            ['id' => 99, 'name' => 'Norwegian Bokmal', 'code' => 'nb'],
            ['id' => 101, 'name' => 'Norwegian Nynorsk', 'code' => 'nn'],
            ['id' => 103, 'name' => 'Polish', 'code' => 'pl'],
            ['id' => 105, 'name' => 'Portuguese (Brazil)', 'code' => 'pt-BR'],
            ['id' => 104, 'name' => 'Portuguese (Portugal)', 'code' => 'pt'],
            ['id' => 106, 'name' => 'Rhaeto-Romanic', 'code' => 'rm'],
            ['id' => 107, 'name' => 'Romanian', 'code' => 'ro'],
            ['id' => 108, 'name' => 'Romanian (Moldavia)', 'code' => 'ro-MD'],
            ['id' => 109, 'name' => 'Russian', 'code' => 'ru'],
            ['id' => 119, 'name' => 'Sami (Lappish)', 'code' => 'se'],
            ['id' => 115, 'name' => 'Serbian', 'code' => 'sr'],
            ['id' => 112, 'name' => 'Slovak', 'code' => 'sk'],
            ['id' => 113, 'name' => 'Slovenian', 'code' => 'sl'],
            ['id' => 111, 'name' => 'Sorbian', 'code' => 'wen'],
            ['id' => 43, 'name' => 'Spanish (Spain-Traditional)', 'code' => 'es'],
            ['id' => 118, 'name' => 'Sutu', 'code' => 'nso'],
            ['id' => 116, 'name' => 'Swedish', 'code' => 'sv'],
            ['id' => 117, 'name' => 'Swedish (Finland)', 'code' => 'sv-FI'],
            ['id' => 120, 'name' => 'Thai', 'code' => 'th'],
            ['id' => 123, 'name' => 'Tsonga', 'code' => 'ts'],
            ['id' => 121, 'name' => 'Tswana', 'code' => 'tn'],
            ['id' => 122, 'name' => 'Turkish', 'code' => 'tr'],
            ['id' => 124, 'name' => 'Ukrainian', 'code' => 'uk'],
            ['id' => 125, 'name' => 'Urdu', 'code' => 'ur'],
            ['id' => 126, 'name' => 'Venda', 'code' => 've'],
            ['id' => 127, 'name' => 'Vietnamese', 'code' => 'vi'],
            ['id' => 128, 'name' => 'Welsh', 'code' => 'cy'],
            ['id' => 129, 'name' => 'Xhosa', 'code' => 'xh'],
            ['id' => 130, 'name' => 'Yiddish', 'code' => 'yi'],
            ['id' => 136, 'name' => 'Zulu', 'code' => 'zu'],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(
                ['id' => $language['id']],
                $language
            );
        }
    }
}
