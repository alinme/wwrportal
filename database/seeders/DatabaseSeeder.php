<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Child;
use App\Models\Foundation;
use App\Models\Group;
use App\Models\School;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->createRoles();

        $admin = User::firstOrCreate(
            ['email' => 'alinmoraru980@gmail.com'],
            [
                'name' => 'Moraru Alin Eduard',
                'password' => bcrypt('PParolamea00'),
                'role' => 'admin',
                'job_title' => 'Facilitator',
            ]
        );
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        Foundation::firstOrCreate(
            ['name' => 'World Vision Romania'],
            ['details' => 'Start in Educatie Program']
        );

        $this->seedSampleSchool();
    }

    protected function createRoles(): void
    {
        foreach (['admin', 'school_manager', 'educator'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    protected function seedSampleSchool(): void
    {
        $campaign = Campaign::firstOrCreate(
            ['name' => 'Brasov - Martie 2026'],
            [
                'facilitator_name' => 'Moraru Alin Eduard',
                'month_year_suffix' => '.03.2026',
                'target_kits' => 600,
                'is_active' => true,
            ]
        );

        $school = School::firstOrCreate(
            ['access_token' => 'demo-school-token-brasov'],
            [
                'campaign_id' => $campaign->id,
                'official_name' => 'Școala Gimnazială Nr. 1 Brașov',
                'address' => 'Str. Republicii 1, Brașov',
                'city' => 'Brașov',
                'state' => 'Brașov',
                'country' => 'Romania',
                'contact_person' => 'Maria Popescu',
                'contact_phone' => '0268 123 456',
                'latitude' => 45.6427,
                'longitude' => 25.5887,
                'target_kits' => 0,
            ]
        );

        $structuresWithGroups = [
            [
                'name' => 'Grădinița cu Program Prelungit Nr. 1',
                'address' => 'Str. Grădiniței 2, Brașov',
                'groups' => [
                    ['Grupa Mare', 'Elena Ionescu', '0722 111 222'],
                    ['Grupa Mijlocie', 'Ana Marinescu', '0733 222 333'],
                ],
            ],
            [
                'name' => 'Grădinița cu Program Prelungit Nr. 2',
                'address' => 'Str. Copiilor 5, Brașov',
                'groups' => [
                    ['Grupa Mică', 'Ioana Stan', null],
                    ['Grupa Pregătitoare', 'Mihaela Radu', '0744 333 444'],
                ],
            ],
            [
                'name' => 'Grădinița Nr. 3',
                'address' => 'Str. Florilor 10, Brașov',
                'groups' => [
                    ['Grupa Albinuțelor', 'Cristina Dumitrescu', null],
                ],
            ],
            [
                'name' => 'Grădinița Nr. 4',
                'address' => null,
                'groups' => [
                    ['Grupa Fluturașilor', 'Daniela Georgescu', '0755 444 555'],
                ],
            ],
        ];

        $childrenPool = [
            ['POPESCU', 'Ion', 'Maria Popescu'],
            ['IONESCU', 'Andrei', 'Elena Ionescu'],
            ['MARINESCU', 'Maria', 'George Marinescu'],
            ['RADU', 'Alexandra', 'Andreea Radu'],
            ['STAN', 'David', 'Ioana Stan'],
            ['DUMITRESCU', 'Sofia', 'Cristina Dumitrescu'],
            ['GEORGESCU', 'Elena', 'Daniela Georgescu'],
            ['NISTOR', 'Mihai', 'Adriana Nistor'],
            ['VLAD', 'Teodora', 'Simona Vlad'],
            ['COSTACHE', 'Stefan', 'Laura Costache'],
            ['MATEI', 'Daria', 'Gabriela Matei'],
            ['FLORESCU', 'Rares', 'Oana Florescu'],
            ['NEAGU', 'Clara', 'Roxana Neagu'],
            ['DINU', 'Paul', 'Monica Dinu'],
        ];
        $childPoolIndex = 0;

        foreach ($structuresWithGroups as $structData) {
            $structure = Structure::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'name' => $structData['name'],
                ],
                [
                    'address' => $structData['address'],
                    'target_kits' => 0,
                ]
            );

            foreach ($structData['groups'] as [$groupName, $educatorName, $contactPhone]) {
                $group = Group::firstOrCreate(
                    [
                        'structure_id' => $structure->id,
                        'name' => $groupName,
                    ],
                    [
                        'educator_name' => $educatorName,
                        'contact_phone' => $contactPhone,
                        'target_kits' => 0,
                    ]
                );

                $numChildren = random_int(3, 6);
                for ($c = 0; $c < $numChildren; $c++) {
                    $entry = $childrenPool[$childPoolIndex % count($childrenPool)];
                    $childPoolIndex++;
                    $childFullName = strtoupper($entry[0].' '.$entry[1]);
                    $parentFullName = $entry[2];
                    Child::firstOrCreate(
                        [
                            'group_id' => $group->id,
                            'child_full_name' => $childFullName,
                        ],
                        [
                            'parent_full_name' => $parentFullName,
                            'gdpr_status' => true,
                        ]
                    );
                }
            }
        }
    }
}
