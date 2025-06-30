<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employeeNames = [
            'AFANGKA, Emily', 'AGBUYA, Jasmine', 'AGRIFINO, Janice', 'ALEJO, Jun', 'ALIGO, Gladys',
            'ALIMURUNG, Alma', 'AMBONG, Abel', 'ANAYDOS, Mary Joyce', 'ANDISO, Esther', 'ANDRADA, Fernando',
            'ANDRES, Joshua', 'APIGO, Angelica Feliz', 'APLATEN, May Ann', 'APOLONIO, Loricel',
            'ASTRERA, Alona Jean', 'ATUAN, Frenzel', 'BACBAC, Joan', 'BACCAY, Elizabeth', 'BAGAWI, Harold',
            'BAG-EO, Ramon', 'BAGNOS, Tetchie Belle', 'BALAAN, Norman', 'BALANZA, Frederick', 'BALANZA, Susan',
            'BALAOING, Imelda', 'BALLOYAN, Sarah', 'BAMBICO, Jan Jan', 'BANAWA, Gerardo', 'BANGNAN, Judelyn',
            'BATAN, Cirilo', 'BATCAGAN, Albanese', 'BAUCAS, Nicasio', 'BAWAYAN, Joy Rose', 'BAYANGDAN, Raul Jr.',
            'BAYODAO, Marcial', 'BERAY, Jocelyn', 'BETA-A, Eunice', 'BETA-A, Jake', 'BISOY, Judy Rose',
            'BLANCO, Marietta', 'BRIONES, Joel', 'BUDAS, Jason', 'BUDIKEY, Glaeson', 'BUGTONG, Michael',
            'CABANILLA, Bernardo, Jr.', 'CABAOIG, Edna', 'CADALIG, Mylene', 'CAEL, Gaston', 'CALANTAS, Octaviano',
            'CALIAS, Jezhelie Mae', 'CAMBOD, Sally', 'CAMILO, Michelle', 'CANOR, Jayvee', 'CAPEGSAN, Cecil',
            'CAPIS, Magdalena', 'CAPUYAN, Cecilia', 'CARDONA, Reina', 'CASAPAO, May-Anne', 'CASTAÑEDA, Adrian',
            'CELO, Lloyd Edward', 'CLAVER, JR Balag-ey', 'CODMOS, Reagan', 'COTENG, Florence', 'CUDAL, Jennifer',
            'DACULAN, Bruce', 'DAGUIO, Danilo', 'DAGUPEN, Cyril', 'DALIDIG, Sharifa', 'DALOG, Roel',
            'DANAO, Jovelyn', 'DANGATAN, Merinda Mae', 'DASAYON, Josie', 'DATIC, Crissa Genice',
            'DAWAYAN, Jennilyn', 'DELA ROSA, Nicasio', 'DELIGEN, Leisley', 'DICKSEN, Julio Earl',
            'DOYAYAG, Urchris', 'DUCAYAG, Ofelia', 'DULAY, Oliver', 'DULNUAN, Micah Carmela', 'ELADJOE, Kevin Joe',
            'ESPOSO, Jhonel', 'ESTANGKI, Olivia', 'ESUSAN, Mara Alyssa', 'FANGASAN, Marcelo', 'FANGASAN, Veronica',
            'FARCANAO, Genevieve', 'FELIX, Mary Ann', 'FRANCO, Edwin Joseph', 'GACAWEN, Jimmy', 'GALLARDO, Noel',
            'GANGA, Karen', 'GANIBAN, Jimmy', 'GASPAR, Melfer', 'GEMINO, Imelda', 'GOLONAN, Arlina',
            'GOMINZA, Jael Jonalyn', 'GONZALES, Lera', 'GOYO, Bentres', 'GUZMAN, Delilah Jesusa', 'HUMIWAT, Nestor',
            'JAVIER, Arnel', 'JAVIER, Maria Kristina Mercedes', 'JIMENEZ, Lea Lynn', 'KALNGAN, Angeline',
            'KIGIS, Karl Glenn', 'KIO-ISEN, Mae', 'KUDAN, Still', 'LA MADRID, Jovy', 'LADILAD, Marvin',
            'LAWINGAN, Remedios', 'LEM-EW, Cherryl', 'LINGBAWAN, Yolanda', 'LOMAS-E, Clifford', 'LORENZO, Efren',
            'LUCAS, Jovinia', 'LUNA, Kevin Jim', 'MAGALGALIT, Jessica', 'MALID-ENG, Dominga', 'MANANGUIT, Maribel',
            'MANGAL-IP, Azon', 'MANG-OSAN, Jhazelyn', 'MANUEL, Arnel', 'MARAFO, Maricel', 'MARINO, Angeline',
            'MAYAO, Joram', 'MAYOS, Jean', 'MENDOZA, Leny', 'MICKLAY, Meriam', 'MOCATI, Lito', 'MOLLANEDA, Rosemarie',
            'MON, Leah', 'MONAYAO, Charlemagne', 'MOSQUEDA, Vilma', 'MUNAR, Marie Claire', 'NABUS, Heather Laxe',
            'NA-OY, Lily Anne', 'NGOWEPAN, Corazon', 'OCHAVE, Kathleen Grace', 'ODANOS, Angel', 'ODIAS, Mannylen',
            'ONSE, Sylvia', 'OWAY, Rey', 'PADAWIL, Godffrey', 'PAGADUAN, Freda', 'PAGTAN, Aida',
            'PAKEO-AN, Mary Ann', 'PAKIPAC, Jonathan', 'PARAÑAL, Ernesto Jr.', 'PASTOR, Miriam',
            'PATNA-AN, Haily', 'PE, Oliver', 'PEKAS, Beverly', 'PICPICAN, Charles', 'PINOS-AN, Pedro',
            'POLON, Reynaldo', 'POTECTAN, Jelly Pearl', 'QUEVADA, John Patrick', 'RANILLE, Ryan',
            'RILLERA, Jikiri', 'ROSADO, Jerson', 'ROSARIO, Crisante', 'SABADO, Joan', 'SABAWAY, Frederick',
            'SAGARIO, Jay', 'SAGAYO, Arlene', 'SAGUILOT, Raponcel', 'SALASA, Henry Gwyn Jonathan',
            'SALES, Precil', 'SALVADOR, Filemon', 'SANTIAGO, Doweno Jr.', 'SICWATEN, Friedy', 'SIMEON, Julius Jr.',
            'SOLIGAM, Cynthia', 'SOPOSOP, Jennifer', 'SORIANO, Ronan', 'SUKAW, Jodelyn', 'SULIPA, Kenneth Paul',
            'SUMALAG, David', 'TAKINAN, Kimberly', 'TALUSIG, Brigette', 'TAPAT, Virginia', 'TEJERO, Marlyn',
            'TELA, Alma Vina', 'TESORO, Rosemarie', 'TIGGANGAY, Benedicto', 'TUBA-ANG, May Ann', 'TUMBAGA, Melchor',
            'ULILA, Luis', 'VALDEZ, Daisy Jane', 'VALENTIN, Amalia', 'VICENTE, Maribel', 'VILLANUEVA, Thelma',
            'VIRADOR, Ellyn', 'WATCHORNA, Marijane', 'WAYET, Delbert', 'YOGYOG, Daisy', 'ZABALA, Ma. Isabel',
        ];

        foreach ($employeeNames as $fullName) {
            $parts = explode(', ', $fullName);
            if (count($parts) === 2) {
                $formattedName = trim($parts[1]).' '.trim($parts[0]);
                Employee::firstOrCreate(['name' => $formattedName]);
            } else {
                $this->command->warn("Skipping malformed employee name: {$fullName}");
            }
        }

        $this->command->info('Seeded the employees table with the official list of names.');
    }
}
