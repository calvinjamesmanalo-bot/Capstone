<?php

$elementaryGradeLevels = [
    'Kinder',
    ...array_map(static fn (int $grade): string => "Grade {$grade}", range(1, 6)),
];

$juniorHighSchoolGradeLevels = array_map(
    static fn (int $grade): string => "Grade {$grade}",
    range(7, 10),
);
$seniorHighSchoolGradeLevels = array_map(
    static fn (int $grade): string => "Grade {$grade}",
    range(11, 12),
);
$latestSchoolYearStart = max(2026, (int) date('Y') + 1);
$sectionsByGrade = [
    'Kinder' => ['A', 'B', 'C'],
    'Grade 1' => ['Amity', 'Adorable', 'Beneficent', 'Blissful'],
    'Grade 2' => ['Charity', 'Diligence', 'Cheerful', 'Dignified', 'Disciplined'],
    'Grade 3' => ['Efficiency', 'Faithfulness', 'Earnest', 'Friendly'],
    'Grade 4' => ['Generosity', 'Humility', 'Grateful', 'Honest'],
    'Grade 5' => ['Independence', 'Joyfulness', 'Industrious', 'Jolly'],
    'Grade 6' => ['Kindness', 'Loyalty', 'Knowledgeable', 'Loving'],
    'Grade 7' => ['Aluminum', 'Antimony', 'Dysprosium', 'Einsteinium', 'Erbium', 'Fluorine', 'Gallium', 'Holmium', 'Hydrogen'],
    'Grade 8' => ['Bismuth', 'Boron', 'Iridium', 'Iron', 'Lead', 'Lithium', 'Magnesium', 'Manganese', 'Mercury'],
    'Grade 9' => ['Carbon', 'Chromium', 'Neon', 'Nickel', 'Nitrogen', 'Osmium', 'Oxygen', 'Platinum', 'Plutonium'],
    'Grade 10' => ['Dubnium', 'Radon', 'Sodium', 'Titanium', 'Uranium', 'Vanadium', 'Xenon'],
    'Grade 11' => ['Adams', 'Aristotle', 'Bacon', 'Bloomberg', 'Copernicus', 'Curie', 'Darwin', 'Drucker', 'Einstein', 'Eliot', 'Farmer', 'Ford', 'Galilei', 'Gates', 'Hawking', 'Hemingway'],
    'Grade 12' => ['Archimedes', 'Avogadro', 'Descartes', 'Eiffel', 'Fleming', 'Fourier', 'Franklin', 'Hume', 'Jenner', 'Jobs', 'Kant', 'Pasteur'],
];

return [
    'school_years' => array_map(
        static fn (int $startYear): string => $startYear.'-'.($startYear + 1),
        range(2010, $latestSchoolYearStart),
    ),
    'grade_level_groups' => [
        'Elementary (Kinder to Grade 6)' => $elementaryGradeLevels,
        'Junior High School (Grade 7 to Grade 10)' => $juniorHighSchoolGradeLevels,
        'Senior High School (Grade 11 to Grade 12)' => $seniorHighSchoolGradeLevels,
    ],
    'grade_levels' => [
        ...$elementaryGradeLevels,
        ...$juniorHighSchoolGradeLevels,
        ...$seniorHighSchoolGradeLevels,
    ],
    'sections_by_grade' => $sectionsByGrade,
    'subjects' => [
        'Kinder' => ['Language', 'Mathematics', 'Filipino', 'Science', 'Reading and Literacy'],
        'Grade 1' => ['Language', 'Reading and Literacy', 'Makabansa', 'Mathematics', 'GMRC'],
        'Grade 2' => ['English', 'Filipino', 'Mathematics', 'Makabansa', 'GMRC'],
        'Grade 3' => ['English', 'Filipino', 'Science', 'Mathematics', 'Makabansa', 'GMRC'],
        'Grade 4' => ['English', 'Filipino', 'Science', 'Mathematics', 'Araling Panlipunan', 'HELE', 'GMRC', 'MAPEH'],
        'Grade 5' => ['English', 'Filipino', 'Science', 'Mathematics', 'Araling Panlipunan', 'HELE', 'Edukasyon sa Pagpapakatao', 'MAPEH'],
        'Grade 6' => ['English', 'Filipino', 'Science', 'Mathematics', 'Araling Panlipunan', 'HELE', 'Edukasyon sa Pagpapakatao', 'MAPEH'],
        'Grade 7' => ['Filipino', 'Mathematics', 'English', 'Science', 'Araling Panlipunan', 'CPTLE', 'MAPEH', 'Edukasyon sa Pagpapakatao'],
        'Grade 8' => ['English', 'Filipino', 'Science', 'Mathematics', 'Araling Panlipunan', 'MAPEH', 'CPTLE', 'Edukasyon sa Pagpapakatao'],
        'Grade 9' => ['English', 'Filipino', 'Science', 'Mathematics', 'Araling Panlipunan', 'CPTLE', 'MAPEH', 'Edukasyon sa Pagpapakatao'],
        'Grade 10' => ['English', 'Filipino', 'Science', 'Mathematics', 'Araling Panlipunan', 'CPTLE', 'MAPEH', 'Edukasyon sa Pagpapakatao'],
    ],
    'shs_subject_groups' => [
        'Grade 11' => [
            'academic_finite_math' => ['sections' => ['Adams', 'Aristotle', 'Bloomberg', 'Bacon', 'Ford', 'Farmer', 'Hemingway'], 'subjects' => ['Effective Communication', 'Finite Mathematics 1', 'General Mathematics', 'General Science', 'Life Skills', 'Mabisang Komunikasyon', 'Pag-aaral sa Lipunan at Kasaysayang Pilipino']],
            'academic_human_movement' => ['sections' => ['Copernicus', 'Curie', 'Darwin', 'Drucker', 'Einstein', 'Eliot'], 'subjects' => ['Effective Communication', 'General Mathematics', 'General Science', 'Human Movement 1 (Basic Anatomy in Sports and Exercise)', 'Life Skills', 'Mabisang Komunikasyon', 'Pag-aaral sa Lipunan at Kasaysayang Pilipino']],
            'techpro_bakery' => ['sections' => ['Hawking'], 'subjects' => ['Bakery Operations', 'Effective Communication', 'General Mathematics', 'General Science', 'Life Skills', 'Mabisang Komunikasyon', 'Pag-aaral sa Lipunan at Kasaysayang Pilipino']],
        ],
        'Grade 12' => [
            'stem' => ['sections' => ['Archimedes', 'Avogadro', 'Fourier', 'Pasteur', 'Franklin', 'Eiffel', 'Jenner'], 'subjects' => ['English for Academic and Professional Purposes', 'General Biology 2', 'General Chemistry 2', 'Physical Education and Health 3', 'Practical Research 2']],
            'abm' => ['sections' => ['Jobs'], 'subjects' => ['Business Finance', 'English for Academic and Professional Purposes', 'Fundamentals of Accountancy, Business and Management 2', 'Physical Education and Health 3', 'Practical Research 3']],
            'humss' => ['sections' => ['Descartes', 'Kant', 'Hume'], 'subjects' => ['Creative Writing', 'English for Academic and Professional Purposes', 'Introduction to World Religions and Belief Systems', 'Physical Education and Health 3', 'Practical Research 2']],
            'tvl' => ['sections' => ['Fleming'], 'subjects' => ['Bread and Pastry Production (NC II) 2', 'Caregiving (NC II) 2', 'English for Academic and Professional Purposes', 'Housekeeping (NC II) 2', 'Physical Education and Health 3', 'Practical Research 2']],
        ],
    ],
];
