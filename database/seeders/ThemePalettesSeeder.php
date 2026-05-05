<?php

namespace Database\Seeders;

use App\Models\ThemePalette;
use App\Models\ThemePaletteColor;
use Illuminate\Database\Seeder;

class ThemePalettesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $palettes = [
            // =========================
            // LIGHT (12)
            // =========================
            [
                'name' => 'Pearl Slate',
                'mode' => 'light',
                'colors' => [
                    'sidebar_bg' => '#0F172A',
                    'sidebar_text' => '#E5E7EB',
                    'sidebar_active_bg' => '#1F2937',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#FFFFFF',
                    'header_text' => '#0F172A',
                    'content_bg' => '#F8FAFC',
                    'content_text' => '#0B1220',
                    'card_bg' => '#FFFFFF',
                    'card_text' => '#0B1220',
                ],
            ],
            [
                'name' => 'Ivory Sand',
                'mode' => 'light',
                'colors' => [
                    'sidebar_bg' => '#111827',
                    'sidebar_text' => '#F3F4F6',
                    'sidebar_active_bg' => '#374151',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#FFFBF5',
                    'header_text' => '#111827',
                    'content_bg' => '#FFF7ED',
                    'content_text' => '#1F2937',
                    'card_bg' => '#FFFFFF',
                    'card_text' => '#111827',
                ],
            ],
            [
                'name' => 'Sapphire Mist',
                'mode' => 'light',
                'colors' => [
                    'sidebar_bg' => '#0B1220',
                    'sidebar_text' => '#E6EDF7',
                    'sidebar_active_bg' => '#123156',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#FFFFFF',
                    'header_text' => '#0B1220',
                    'content_bg' => '#F5F9FF',
                    'content_text' => '#0B1220',
                    'card_bg' => '#FFFFFF',
                    'card_text' => '#0B1220',
                ],
            ],
            [
                'name' => 'Emerald Paper',
                'mode' => 'light',
                'colors' => [
                    'sidebar_bg' => '#0B1F17',
                    'sidebar_text' => '#E7F7F0',
                    'sidebar_active_bg' => '#0F3D2E',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#FFFFFF',
                    'header_text' => '#0B1F17',
                    'content_bg' => '#F3FBF7',
                    'content_text' => '#0B1F17',
                    'card_bg' => '#FFFFFF',
                    'card_text' => '#0B1F17',
                ],
            ],
            [
                'name' => 'Rose Quartz',
                'mode' => 'light',
                'colors' => [
                    'sidebar_bg' => '#2D1B2D',
                    'sidebar_text' => '#FCEEF3',
                    'sidebar_active_bg' => '#5B3A5B',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#FFFFFF',
                    'header_text' => '#2D1B2D',
                    'content_bg' => '#FEF7FB',
                    'content_text' => '#2D1B2D',
                    'card_bg' => '#FFFFFF',
                    'card_text' => '#2D1B2D',
                ],
            ],
            [
                'name' => 'Amber Glow',
                'mode' => 'light',
                'colors' => [
                    'sidebar_bg' => '#2D1F0F',
                    'sidebar_text' => '#FFF8E1',
                    'sidebar_active_bg' => '#5B3F1F',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#FFFFFF',
                    'header_text' => '#2D1F0F',
                    'content_bg' => '#FFFBF0',
                    'content_text' => '#2D1F0F',
                    'card_bg' => '#FFFFFF',
                    'card_text' => '#2D1F0F',
                ],
            ],

            // NEW LIGHT (6) => total light = 12
            [
                'name' => 'Glacier Steel',
                'mode' => 'light',
                'colors' => [
                    'sidebar_bg' => '#0B1324',
                    'sidebar_text' => '#E6EAF2',
                    'sidebar_active_bg' => '#162744',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#FFFFFF',
                    'header_text' => '#0B1324',
                    'content_bg' => '#F4F7FB',
                    'content_text' => '#0B1324',
                    'card_bg' => '#FFFFFF',
                    'card_text' => '#0B1324',
                ],
            ],
            [
                'name' => 'Linen Graphite',
                'mode' => 'light',
                'colors' => [
                    'sidebar_bg' => '#14161A',
                    'sidebar_text' => '#F1F2F4',
                    'sidebar_active_bg' => '#2A2F36',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#FFFEFC',
                    'header_text' => '#14161A',
                    'content_bg' => '#FAF7F2',
                    'content_text' => '#1B1F24',
                    'card_bg' => '#FFFFFF',
                    'card_text' => '#14161A',
                ],
            ],
            [
                'name' => 'Aegean Teal',
                'mode' => 'light',
                'colors' => [
                    'sidebar_bg' => '#062024',
                    'sidebar_text' => '#DDF4F4',
                    'sidebar_active_bg' => '#0B3A40',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#FFFFFF',
                    'header_text' => '#062024',
                    'content_bg' => '#F2FBFB',
                    'content_text' => '#062024',
                    'card_bg' => '#FFFFFF',
                    'card_text' => '#062024',
                ],
            ],
            [
                'name' => 'Nordic Olive',
                'mode' => 'light',
                'colors' => [
                    'sidebar_bg' => '#1A2016',
                    'sidebar_text' => '#EEF3EA',
                    'sidebar_active_bg' => '#2F3A26',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#FFFFFF',
                    'header_text' => '#1A2016',
                    'content_bg' => '#F6FAF3',
                    'content_text' => '#1A2016',
                    'card_bg' => '#FFFFFF',
                    'card_text' => '#1A2016',
                ],
            ],
            [
                'name' => 'Platinum Navy',
                'mode' => 'light',
                'colors' => [
                    'sidebar_bg' => '#0A1530',
                    'sidebar_text' => '#E7ECF7',
                    'sidebar_active_bg' => '#122556',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#FFFFFF',
                    'header_text' => '#0A1530',
                    'content_bg' => '#F5F7FC',
                    'content_text' => '#0A1530',
                    'card_bg' => '#FFFFFF',
                    'card_text' => '#0A1530',
                ],
            ],
            [
                'name' => 'Cocoa Cream',
                'mode' => 'light',
                'colors' => [
                    'sidebar_bg' => '#241A14',
                    'sidebar_text' => '#FFF3E8',
                    'sidebar_active_bg' => '#3A2A20',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#FFFFFF',
                    'header_text' => '#241A14',
                    'content_bg' => '#FFFAF6',
                    'content_text' => '#241A14',
                    'card_bg' => '#FFFFFF',
                    'card_text' => '#241A14',
                ],
            ],

            // =========================
            // DARK (12)
            // =========================
            [
                'name' => 'Obsidian',
                'mode' => 'dark',
                'colors' => [
                    'sidebar_bg' => '#0B1220',
                    'sidebar_text' => '#D1D5DB',
                    'sidebar_active_bg' => '#111827',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#0B1220',
                    'header_text' => '#FFFFFF',
                    'content_bg' => '#070B12',
                    'content_text' => '#E5E7EB',
                    'card_bg' => '#0F172A',
                    'card_text' => '#E5E7EB',
                ],
            ],
            [
                'name' => 'Charcoal Gold',
                'mode' => 'dark',
                'colors' => [
                    'sidebar_bg' => '#0A0A0A',
                    'sidebar_text' => '#E5E5E5',
                    'sidebar_active_bg' => '#171717',
                    'sidebar_active_text' => '#F59E0B',
                    'header_bg' => '#0A0A0A',
                    'header_text' => '#F5F5F5',
                    'content_bg' => '#050505',
                    'content_text' => '#EDEDED',
                    'card_bg' => '#111111',
                    'card_text' => '#F5F5F5',
                ],
            ],
            [
                'name' => 'Midnight Sapphire',
                'mode' => 'dark',
                'colors' => [
                    'sidebar_bg' => '#071024',
                    'sidebar_text' => '#D6E1FF',
                    'sidebar_active_bg' => '#0B1B3A',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#071024',
                    'header_text' => '#EAF0FF',
                    'content_bg' => '#050B18',
                    'content_text' => '#D6E1FF',
                    'card_bg' => '#0B1633',
                    'card_text' => '#EAF0FF',
                ],
            ],
            [
                'name' => 'Forest Night',
                'mode' => 'dark',
                'colors' => [
                    'sidebar_bg' => '#071A12',
                    'sidebar_text' => '#D7F7E8',
                    'sidebar_active_bg' => '#0B2A1D',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#071A12',
                    'header_text' => '#E9FFF6',
                    'content_bg' => '#04110C',
                    'content_text' => '#D7F7E8',
                    'card_bg' => '#0A2016',
                    'card_text' => '#E9FFF6',
                ],
            ],
            [
                'name' => 'Crimson Velvet',
                'mode' => 'dark',
                'colors' => [
                    'sidebar_bg' => '#1A0B0F',
                    'sidebar_text' => '#FADBD8',
                    'sidebar_active_bg' => '#34161C',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#1A0B0F',
                    'header_text' => '#FCE4E1',
                    'content_bg' => '#0F0608',
                    'content_text' => '#FADBD8',
                    'card_bg' => '#241216',
                    'card_text' => '#FCE4E1',
                ],
            ],
            [
                'name' => 'Royal Purple',
                'mode' => 'dark',
                'colors' => [
                    'sidebar_bg' => '#0F0A1A',
                    'sidebar_text' => '#E0D7FF',
                    'sidebar_active_bg' => '#1E1534',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#0F0A1A',
                    'header_text' => '#EBE0FF',
                    'content_bg' => '#08060F',
                    'content_text' => '#E0D7FF',
                    'card_bg' => '#161024',
                    'card_text' => '#EBE0FF',
                ],
            ],

            // NEW DARK (6) => total dark = 12
            [
                'name' => 'Carbon Blue',
                'mode' => 'dark',
                'colors' => [
                    'sidebar_bg' => '#070D16',
                    'sidebar_text' => '#DCE6F5',
                    'sidebar_active_bg' => '#0D1B2E',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#070D16',
                    'header_text' => '#EAF2FF',
                    'content_bg' => '#050912',
                    'content_text' => '#DCE6F5',
                    'card_bg' => '#0B1526',
                    'card_text' => '#EAF2FF',
                ],
            ],
            [
                'name' => 'Onyx Teal',
                'mode' => 'dark',
                'colors' => [
                    'sidebar_bg' => '#061318',
                    'sidebar_text' => '#CFF3F1',
                    'sidebar_active_bg' => '#0A242C',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#061318',
                    'header_text' => '#E4FFFE',
                    'content_bg' => '#040C10',
                    'content_text' => '#CFF3F1',
                    'card_bg' => '#071D24',
                    'card_text' => '#E4FFFE',
                ],
            ],
            [
                'name' => 'Burgundy Noir',
                'mode' => 'dark',
                'colors' => [
                    'sidebar_bg' => '#14070B',
                    'sidebar_text' => '#F6D8DE',
                    'sidebar_active_bg' => '#2A0F16',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#14070B',
                    'header_text' => '#FFE7EB',
                    'content_bg' => '#0C0406',
                    'content_text' => '#F6D8DE',
                    'card_bg' => '#1F0B11',
                    'card_text' => '#FFE7EB',
                ],
            ],
            [
                'name' => 'Deep Copper',
                'mode' => 'dark',
                'colors' => [
                    'sidebar_bg' => '#120C08',
                    'sidebar_text' => '#F6E7DE',
                    'sidebar_active_bg' => '#24160F',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#120C08',
                    'header_text' => '#FFF1E9',
                    'content_bg' => '#0A0705',
                    'content_text' => '#F6E7DE',
                    'card_bg' => '#1B110B',
                    'card_text' => '#FFF1E9',
                ],
            ],
            [
                'name' => 'Slate Violet',
                'mode' => 'dark',
                'colors' => [
                    'sidebar_bg' => '#0C0B16',
                    'sidebar_text' => '#E7E3FF',
                    'sidebar_active_bg' => '#181634',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#0C0B16',
                    'header_text' => '#F1EDFF',
                    'content_bg' => '#060610',
                    'content_text' => '#E7E3FF',
                    'card_bg' => '#12102A',
                    'card_text' => '#F1EDFF',
                ],
            ],
            [
                'name' => 'Arctic Charcoal',
                'mode' => 'dark',
                'colors' => [
                    'sidebar_bg' => '#0A0D10',
                    'sidebar_text' => '#E6EAF0',
                    'sidebar_active_bg' => '#141A20',
                    'sidebar_active_text' => '#FFFFFF',
                    'header_bg' => '#0A0D10',
                    'header_text' => '#F2F6FB',
                    'content_bg' => '#06080A',
                    'content_text' => '#E6EAF0',
                    'card_bg' => '#10151B',
                    'card_text' => '#F2F6FB',
                ],
            ],
        ];

        foreach ($palettes as $paletteData) {
            $palette = ThemePalette::firstOrCreate(
                [
                    'name' => $paletteData['name'],
                    'mode' => $paletteData['mode'],
                ],
                [
                    'is_premium' => true,
                    'is_active' => true,
                ]
            );

            // Delete existing colors and recreate (ke update bhi ho jaye)
            $palette->colors()->delete();

            foreach ($paletteData['colors'] as $slot => $hex) {
                ThemePaletteColor::create([
                    'theme_palette_id' => $palette->id,
                    'slot' => $slot,
                    'hex' => $hex,
                ]);
            }
        }
    }
}
