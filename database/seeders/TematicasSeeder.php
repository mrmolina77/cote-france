<?php

namespace Database\Seeders;

use App\Models\Capitulo;
use App\Models\Tematica;
use Illuminate\Database\Seeder;

class TematicasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tematicas = [
            1 => [
                'U1' => [
                    'LES OBJETS DE LA CLASSE',
                    'LA PRONONCIATION',
                    'LES NUMÉROS',
                    'LES VERBES DU 1ER GROUPE',
                    'SALUER',
                    'SE PRÉSENTER',
                    'LA DATE',
                    'BILAN+TRADUCTION',
                ],
                'U2' => [
                    'LA FAMILLE',
                    'LES ADJECTIFS POSSESSIFS',
                    'LES ADJECTIFS DÉMONSTRATIFS',
                    'LES COULEURS',
                    "L'HEURE",
                    'LES GROUPES DE VERBES',
                    'RAPPEL DES ADJS INTERROGATIFS',
                    "SE POSITIONNER DANS L'ESPACE",
                    "INTRO À L'IMPÉRATIF",
                    'LES PAYS ET NATIONALITÉS',
                    'BILAN +TRADUCTION',
                ],
                'U3' => [
                    'LE PETIT DÉJEUNER + PARTITIFS',
                    'LES EXPRESSIONS AVEC AVOIR',
                    'LES COUVERTS',
                    'LES EXPRESSIONS AU CAFÉ',
                    'LES PRONOMS COD - COI',
                    'FAIRE LES COURSES',
                    'LES COMPARATIFS ET SUPERLATIFS',
                    'BILAN ET TRADUCTION',
                ],
                'U4' => [
                    'LA DESCRIPTION PHYSIQUE',
                    'LES VERBES DU DEUXIÈME GROUPE',
                    'LE FEMININ DES ADJECTIFS',
                    'LES COMPARATIFS ET SUPERLATIFS RAPPEL',
                    'LEQUEL/LAQUELLE…',
                    'LA DESCRIPTION MORALE',
                    'JEUX "QUI EST-CE"',
                    'BILAN ET TRADUCTION',
                ],
                'U5' => [
                    "L'IMPÉRATIF",
                    'LA VILLE',
                    'LES MOYENS DE TRANSPORT',
                    'LES NOMBRES ORDINAUX',
                    'LES DIRECTIONS',
                    'BILAN ET TRADUCTION',
                ],
                'U6' => [
                    'LES PIÈCES DE LA MAISON',
                    'LES OBJETS DE LA MAISON',
                    'LE PASSÉ RÉCENT, ÊTRE EN TRAIN DE, FUTUR PROCHE',
                    'LOUER UN APPARTEMENT ET LES ANNONCES',
                    'BILAN ET TRADUCTION',
                ],
            ],
            2 => [
                'U1' => [
                    'LE PASSÉ COMPOSÉ AVEC AVOIR',
                    'LES COMMERCES',
                    'LA BANQUE',
                    'LE BOULANGER ET LE FROMAGER',
                    'LE BUREAU DE TABAC',
                    'LES VÊTEMENTS',
                    'BILAN ET TRADUCTION',
                ],
                'U2' => [
                    'LE PASSÉ COMPOSÉ AVEC ÊTRE',
                    'LES LOISIRS',
                    'EXPRIMER SES GOÛTS',
                    'LES SORTIES',
                    'LE BRICOLAGE',
                    'LES SPORTS',
                    'BILAN ET TRADUCTION',
                ],
                'U3' => [
                    'LES ACTIVITÉS QUOTIDIENNES ET PC AVEC PRONOMINAUX',
                    'LA COLOCATION',
                    'LES TÂCHES MÉNAGÈRES',
                    'BILAN ET TRADUCTION',
                ],
                'U4' => [
                    'LE CORPS HUMAIN',
                    'LES SENS',
                    'LE GÉRONDIF',
                    'CHEZ LE DOCTEUR ET LES BLESSURES',
                    'BILAN ET TRADUCTION',
                ],
                'U5' => [
                    'LES AUXILIAIRES DE MODE',
                    'LES CONNECTEURS LOGIQUES',
                    'RÉSERVER UN BILLET DE TRAIN',
                    'BILAN ET TRADUCTION',
                ],
                'U6' => [
                    'LE FUTUR SIMPLE',
                    'LES SAISONS ET LA MÉTÉO',
                    'LA PLAGE',
                    "LA DESCRIPTION D'UNE IMAGE",
                    'LES PRONOMS Y ET EN',
                    'BILAN ET TRADUCTION',
                ],
            ],
            3 => [
                'U1' => [
                    "L'IMPARFAIT",
                    'LE SYSTÈME SCOLAIRE',
                    'LES MÉTIERS',
                    'BILAN ET TRADUCTION',
                ],
                'U2' => [
                    'LE PASSÉ COMPOSÉ ET L’IMPARFAIT',
                    'LE CINÉMA',
                    'BILAN ET TRADUCTION',
                ],
                'U3' => [
                    'LES ADJECTIFS ET PRONOMS INDÉFINIS',
                    'LA VISITE DE PARIS',
                    'LES ADVERBES',
                    "PARISIEN MODE D'EMPLOI",
                    'BILAN ET TRADUCTION',
                ],
                'U4' => [
                    'LE SUBJONCTIF',
                    'LE PERMIS DE CONDUIRE',
                    'BILAN ET TRADUCTION',
                ],
                'U5' => [
                    'LE CONDITIONNEL',
                    'LA FRANCE ET SA DIVERSITÉ',
                    'BILAN ET TRADUCTION',
                ],
                'U6' => [
                    'LES PRONOMS COMPLÉMENTS DOUBLES',
                    "L'IMPÉRATIF",
                    "L'IMPÉRATIF DES PRONOMS COMPLÉMENTS DOUBLES",
                    'COMMENT SONT LES FRANÇAIS',
                    'BILAN ET TRADUCTION',
                ],
            ],
            4 => [
                'U1' => [
                    'LE FUTUR ANTÉRIEUR',
                    'LA GASTRONOMIE',
                    'BILAN ET TRADUCTION',
                ],
                'U2' => [
                    'PLUS-QUE-PARFAIT',
                    "L'ARGOT",
                    'BILAN ET TRADUCTION',
                ],
                'U3' => [
                    'LES VERBES PRONOMINAUX RÉCIPROQUES',
                    'FAIRE UNE RENCONTRE',
                    'LA DEMANDE EN MARIAGE',
                    'BILAN ET TRADUCTION',
                ],
                'U4' => [
                    "LES TEMPS DE L'INDICATIF",
                    'LE VOYAGE DE NOCE',
                    "À L'AÉROPORT",
                    "À L'HÔTEL",
                    'LA DISPUTE',
                    'BILAN ET TRADUCTION',
                ],
                'U5' => [
                    'LE PASSÉ SIMPLE',
                    "L'HISTOIRE DE FRANCE",
                ],
                'U6' => [
                    'LE CONDITIONNEL PASSÉ',
                    'LA CONDITION AVEC SI AU CONDITIONNEL PASSÉ',
                    '1 AN DÉJÀ !',
                ],
            ],
        ];

        foreach ($tematicas as $nivelId => $unidades) {
            foreach ($unidades as $codigoUnidad => $descripciones) {
                $capitulo = Capitulo::where('nivel_id', $nivelId)
                    ->where('capitulo_codigo', $codigoUnidad)
                    ->first();

                if (!$capitulo) {
                    continue;
                }

                foreach ($descripciones as $orden => $descripcion) {
                    Tematica::updateOrCreate(
                        [
                            'capitulo_id' => $capitulo->capitulo_id,
                            'tematica_descripcion' => $descripcion,
                        ],
                        [
                            'orden' => $orden + 1,
                            'tematica_activo' => true,
                        ]
                    );
                }
            }
        }
    }
}
