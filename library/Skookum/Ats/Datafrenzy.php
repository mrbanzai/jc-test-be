<?php
/**
 * Base ATS class implementing the interface.
 */
class Skookum_Ats_Datafrenzy
    extends Skookum_Ats
    implements Skookum_Ats_Interface {

    /**
     * QueryPath object.
     * @var QueryPath
     */
    protected $_qp;

    /**
     * The default constructor. Uses dependency injection to load any
     * requirements set forth by the interface.
     *
     * @access  public
     * @param   QueryPath   $qp
     */
    public function __construct(QueryPath $qp)
    {
        // store the query path object
        $this->_qp = $qp;
    }

    /**
     * Scrape the job listings page.
     *
     * @access  public
     * @param   array   $jobList
     * @param   string  $jobListType
     * @param   int     $page
     * @return  array
     */
    public function scrapeJobListings($jobList, $jobListType = 'XML', $page = 1)
    {
        // for storing matches
        $matches = array();

        // grab the content
        try {

            // make a cURL request
            $data = $this->request($jobList['url']);
            if ($data) {
                // grab options based on the job list type
                $options = $this->getOptionsByType($jobListType);

                // load the data into QueryPath
                libxml_use_internal_errors(TRUE);
                $this->_qp->load($data, $options);
                libxml_clear_errors();
                
                // find data matches (job detail urls)
                $results = $this->_qp->find('JobPosting Job');
                foreach ($results as $r) {

                    // do some gruntwork to determine the job url
                    $job_url = $jobList['url'];
                    $job_email = $r->branch()->find('JobEmail')->textImplode();
                    if (!empty($job_email)) {
                        // find characters before .1030
                        $pos = strpos($job_email, '.1030');
                        if ($pos !== FALSE) {
                            $job_number = substr($job_email, 0, $pos);
                            $job_url = 'http://candidate.datafrenzy.com/jobs/details.aspx?title=' . urlencode($job_number);
                        }
                    }

                    $details = array(
                        'name' => $r->branch()->find('JobTitle')->textImplode(),
                        'job_id' => $r->branch()->find('JobCode')->textImplode(),
                        'company' => $r->branch()->find('BranchName')->textImplode(),
                        'location' => $r->branch()->find('JobCity')->textImplode() . ", " . $r->branch()->find('JobState')->textImplode(),
                        'category' => $this->getCategoryFromCode($r->branch()->find('JobOccupationCodeID')->textImplode()),
                        'department' => $this->getCategoryFromCode($r->branch()->find('JobOccupationCodeID')->textImplode(), 'major'),
                        'schedule' => $this->getTypeFromCode($r->branch()->find('JobEmployTypeID')->textImplode()),
                        'description' => str_replace('df-hr', '', $r->branch()->find('JobDesc')->textImplode()),
                        'apply_url' => $r->branch()->find('JobApplyURL')->textImplode(),
                        'years_exp' => $this->getExperienceFromCode($r->branch()->find('JobYearsExperienceID')->textImplode()),
                        'job_url' => $job_url
                    );

                    array_push($matches, $details);
                }
            }

        } catch (Exception $e) {
            error_log($e);
            throw new Exception($e);
        }

        return $matches;
    }

    /**
     * Scrape a job details page.
     *
     * @access  public
     * @param   string  $jobDetailsUrl
     * @param   string  $referrerUrl
     * @param   string  $jobDetailsType
     */
    public function scrapeJobDetails($jobDetailsUrl, $referrerUrl, $jobDetailsType = 'HTML')
    {}

    /**
     * Scrapes the job listings page for pagination details.
     *
     * @access  public
     * @return  mixed
     */
    public function getPaginationDetails($data, $curPage = 1)
    {
        // return data
        return array('curPage' => 1, 'totalPages' => 1);
    }

    /**
     * Map job experience in years.
     *
     * @access  private
     * @param   string  $code
     * @return  mixed
     */
    private function getExperienceFromCode($code) {
        $experience_map = array(
            "1" => 0,
            "2"	=> 1,
            "3" => 3,
            "4" => 4,
            "5" => 5
        );
        $code = $code . "";
        if(!isset($experience_map[$code])) return 0;
        return $experience_map[$code];
    }

    /**
     * Map job type from code.
     *
     * @access  private
     * @param   string  $code
     * @return  mixed
     */
    private function getTypeFromCode($code) {
        $code_map = array(
            "0" => "",
            "1" => "Full Time",
            "2" => "Contract to Perm",
            "3" => "0-3 Month Contract",
            "4" => "3-6 Month Contract",
            "5" => "6-9 Month Contract",
            "6" => "9+ Month Contract",
            "7" => "Part-Time",
            "8" => "Temporary",
            "9" => "Internship"
        );
        $code = $code . "";
        if(!isset($code_map[$code])) return "";
        return $code_map[$code];
    }

    /**
     * Map job category from code.
     *
     * @access  private
     * @param   string  $code
     * @return  mixed
     */
    private function getCategoryFromCode($code, $col='minor') {
        $category_map = array(
            "2100" => array("major" => "Accounting / Human Resources", "minor" => "Accounting / Human Resources- General"),
            "2101" => array("major" => "Accounting / Human Resources", "minor" => "Accountant - General"),
            "2102" => array("major" => "Accounting / Human Resources", "minor" => "Accountant - Financial"),
            "2103" => array("major" => "Accounting / Human Resources", "minor" => "Accountant - Tax"),
            "2104" => array("major" => "Accounting / Human Resources", "minor" => "Accounts Receivable"),
            "2105" => array("major" => "Accounting / Human Resources", "minor" => "Accounts Payable"),
            "2106" => array("major" => "Accounting / Human Resources", "minor" => "Analyst"),
            "2107" => array("major" => "Accounting / Human Resources", "minor" => "Auditor"),
            "2108" => array("major" => "Accounting / Human Resources", "minor" => "Billing"),
            "2109" => array("major" => "Accounting / Human Resources", "minor" => "Bookkeeper"),
            "2110" => array("major" => "Accounting / Human Resources", "minor" => "Consultant"),
            "2111" => array("major" => "Accounting / Human Resources", "minor" => "Controller"),
            "2112" => array("major" => "Accounting / Human Resources", "minor" => "HR Manager"),
            "2113" => array("major" => "Accounting / Human Resources", "minor" => "HR Recruiter"),
            "2114" => array("major" => "Accounting / Human Resources", "minor" => "HR Technical Recruiter"),
            "2115" => array("major" => "Accounting / Human Resources", "minor" => "Payroll / Benefits"),
            "2200" => array("major" => "Administrative / Clerical", "minor" => "Administrative / Clerical - General"),
            "2201" => array("major" => "Administrative / Clerical", "minor" => "Administrative Assistant"),
            "2202" => array("major" => "Administrative / Clerical", "minor" => "Buyer / Purchasing"),
            "2203" => array("major" => "Administrative / Clerical", "minor" => "Customer Service"),
            "2204" => array("major" => "Administrative / Clerical", "minor" => "Legal Assistant"),
            "2205" => array("major" => "Administrative / Clerical", "minor" => "Receptionist"),
            "2206" => array("major" => "Administrative / Clerical", "minor" => "Secretarial"),
            "2207" => array("major" => "Administrative / Clerical", "minor" => "Collections"),
            "2208" => array("major" => "Administrative / Clerical", "minor" => "Employee Services"),
            "2300" => array("major" => "Banking / Finance / Insurance", "minor" => "Banking / Finance / Insurance - General"),
            "2301" => array("major" => "Banking / Finance / Insurance", "minor" => "Actuary"),
            "2302" => array("major" => "Banking / Finance / Insurance", "minor" => "Bank Operations"),
            "2303" => array("major" => "Banking / Finance / Insurance", "minor" => "Bank Teller"),
            "2304" => array("major" => "Banking / Finance / Insurance", "minor" => "Banking Sales"),
            "2305" => array("major" => "Banking / Finance / Insurance", "minor" => "Claims / Adjuster"),
            "2306" => array("major" => "Banking / Finance / Insurance", "minor" => "Financial Analyst"),
            "2307" => array("major" => "Banking / Finance / Insurance", "minor" => "Financial Sales"),
            "2308" => array("major" => "Banking / Finance / Insurance", "minor" => "Insurance Sales"),
            "2309" => array("major" => "Banking / Finance / Insurance", "minor" => "Insurance Operations"),
            "2310" => array("major" => "Banking / Finance / Insurance", "minor" => "Investments"),
            "2311" => array("major" => "Banking / Finance / Insurance", "minor" => "Loan / Mortgage"),
            "2312" => array("major" => "Banking / Finance / Insurance", "minor" => "Stock Broker / Securities Trader"),
            "2313" => array("major" => "Banking / Finance / Insurance", "minor" => "Underwriting"),
            "2314" => array("major" => "Banking / Finance / Insurance", "minor" => "Product Development"),
            "2400" => array("major" => "Engineering", "minor" => "Engineering - General"),
            "2401" => array("major" => "Engineering", "minor" => "Aerospace"),
            "2402" => array("major" => "Engineering", "minor" => "Agriculture"),
            "2403" => array("major" => "Engineering", "minor" => "Bio-Medical"),
            "2404" => array("major" => "Engineering", "minor" => "Chemical"),
            "2405" => array("major" => "Engineering", "minor" => "Civil"),
            "2406" => array("major" => "Engineering", "minor" => "Electrical"),
            "2407" => array("major" => "Engineering", "minor" => "Electronic"),
            "2408" => array("major" => "Engineering", "minor" => "Engineering Consulting"),
            "2409" => array("major" => "Engineering", "minor" => "Engineering Management"),
            "2410" => array("major" => "Engineering", "minor" => "Environmental"),
            "2411" => array("major" => "Engineering", "minor" => "Industrial"),
            "2412" => array("major" => "Engineering", "minor" => "Manufacturing"),
            "2413" => array("major" => "Engineering", "minor" => "Marine"),
            "2414" => array("major" => "Engineering", "minor" => "Mechanical"),
            "2415" => array("major" => "Engineering", "minor" => "Metallurgical / Materials"),
            "2416" => array("major" => "Engineering", "minor" => "Mining"),
            "2417" => array("major" => "Engineering", "minor" => "Nuclear"),
            "2418" => array("major" => "Engineering", "minor" => "Optical"),
            "2419" => array("major" => "Engineering", "minor" => "Packaging"),
            "2420" => array("major" => "Engineering", "minor" => "Petroleum"),
            "2421" => array("major" => "Engineering", "minor" => "Process"),
            "2422" => array("major" => "Engineering", "minor" => "Project"),
            "2423" => array("major" => "Engineering", "minor" => "Quality"),
            "2424" => array("major" => "Engineering", "minor" => "Structural"),
            "2500" => array("major" => "General / Other", "minor" => "General / Other - General"),
            "2501" => array("major" => "General / Other", "minor" => "Assembly / Production"),
            "2502" => array("major" => "General / Other", "minor" => "Construction Worker"),
            "2503" => array("major" => "General / Other", "minor" => "Day Laborer"),
            "2504" => array("major" => "General / Other", "minor" => "Driver"),
            "2505" => array("major" => "General / Other", "minor" => "Electrician"),
            "2506" => array("major" => "General / Other", "minor" => "Entertainment / Gaming"),
            "2507" => array("major" => "General / Other", "minor" => "Groundskeeping"),
            "2508" => array("major" => "General / Other", "minor" => "Hotel Staff"),
            "2509" => array("major" => "General / Other", "minor" => "Law Enforcement"),
            "2510" => array("major" => "General / Other", "minor" => "Mechanic"),
            "2511" => array("major" => "General / Other", "minor" => "Protective / Security"),
            "2512" => array("major" => "General / Other", "minor" => "Restaurant Staff"),
            "2513" => array("major" => "General / Other", "minor" => "Repair / Technician"),
            "2514" => array("major" => "General / Other", "minor" => "Skilled Trades"),
            "2515" => array("major" => "General / Other", "minor" => "Training / Development"),
            "2516" => array("major" => "General / Other", "minor" => "Travel"),
            "2517" => array("major" => "General / Other", "minor" => "Warehouse"),
            "2518" => array("major" => "General / Other", "minor" => "Writer / Editor"),
            "2519" => array("major" => "General / Other", "minor" => "Animal Groomer"),
            "2520" => array("major" => "General / Other", "minor" => "Animal Trainer"),
            "2521" => array("major" => "General / Other", "minor" => "General Automotive"),
            "2522" => array("major" => "General / Other", "minor" => "Aircraft General Business"),
            "2523" => array("major" => "General / Other", "minor" => "Flight Operations"),
            "2600" => array("major" => "Health Care", "minor" => "Health Care - General"),
            "2601" => array("major" => "Health Care", "minor" => "Administration"),
            "2602" => array("major" => "Health Care", "minor" => "Billing / Collections"),
            "2603" => array("major" => "Health Care", "minor" => "CNT"),
            "2604" => array("major" => "Health Care", "minor" => "Dental Assistant"),
            "2605" => array("major" => "Health Care", "minor" => "Dentist"),
            "2606" => array("major" => "Health Care", "minor" => "Dietary / Nutrition"),
            "2607" => array("major" => "Health Care", "minor" => "Executive"),
            "2608" => array("major" => "Health Care", "minor" => "Home Healthcare"),
            "2609" => array("major" => "Health Care", "minor" => "Laboratory"),
            "2610" => array("major" => "Health Care", "minor" => "Medical Technician"),
            "2611" => array("major" => "Health Care", "minor" => "Medical Records"),
            "2612" => array("major" => "Health Care", "minor" => "Mental Health"),
            "2613" => array("major" => "Health Care", "minor" => "Nursing"),
            "2614" => array("major" => "Health Care", "minor" => "Optician"),
            "2615" => array("major" => "Health Care", "minor" => "Patient Care / Management"),
            "2616" => array("major" => "Health Care", "minor" => "Pharmacist"),
            "2617" => array("major" => "Health Care", "minor" => "Physician"),
            "2618" => array("major" => "Health Care", "minor" => "Radiation Technologist"),
            "2619" => array("major" => "Health Care", "minor" => "Therapist"),
            "2620" => array("major" => "Health Care", "minor" => "Regulatory"),
            "2621" => array("major" => "Health Care", "minor" => "Pharmaceutical - Clinical"),
            "2622" => array("major" => "Health Care", "minor" => "Pharmaceutical Preclinical Development"),
            "2623" => array("major" => "Health Care", "minor" => "Research"),
            "2700" => array("major" => "Information Systems", "minor" => "Information Systems - General"),
            "2701" => array("major" => "Information Systems", "minor" => "Business Analyst"),
            "2702" => array("major" => "Information Systems", "minor" => "CAD / AutoCAD"),
            "2703" => array("major" => "Information Systems", "minor" => "Consulting"),
            "2704" => array("major" => "Information Systems", "minor" => "Database Administrator"),
            "2705" => array("major" => "Information Systems", "minor" => "Database Developer"),
            "2706" => array("major" => "Information Systems", "minor" => "Graphics / Multimedia"),
            "2707" => array("major" => "Information Systems", "minor" => "Hardware Engineer"),
            "2708" => array("major" => "Information Systems", "minor" => "Hardware Technician"),
            "2709" => array("major" => "Information Systems", "minor" => "Help Desk / Technical Support"),
            "2710" => array("major" => "Information Systems", "minor" => "Management"),
            "2711" => array("major" => "Information Systems", "minor" => "Network Administrator"),
            "2712" => array("major" => "Information Systems", "minor" => "Project Manager"),
            "2713" => array("major" => "Information Systems", "minor" => "Quality Assurance"),
            "2714" => array("major" => "Information Systems", "minor" => "Security"),
            "2715" => array("major" => "Information Systems", "minor" => "Software Engineer"),
            "2716" => array("major" => "Information Systems", "minor" => "Systems Administrator"),
            "2717" => array("major" => "Information Systems", "minor" => "Systems Analyst"),
            "2718" => array("major" => "Information Systems", "minor" => "Technical Writer"),
            "2719" => array("major" => "Information Systems", "minor" => "Trainer"),
            "2720" => array("major" => "Information Systems", "minor" => "Web Developer"),
            "2721" => array("major" => "Information Systems", "minor" => "Configuration Management"),
            "2800" => array("major" => "Management", "minor" => "Management - General"),
            "2801" => array("major" => "Management", "minor" => "Administration"),
            "2802" => array("major" => "Management", "minor" => "Construction"),
            "2803" => array("major" => "Management", "minor" => "Consultant"),
            "2804" => array("major" => "Management", "minor" => "Finance"),
            "2805" => array("major" => "Management", "minor" => "Hotel / Restaurant"),
            "2806" => array("major" => "Management", "minor" => "Laboratory"),
            "2807" => array("major" => "Management", "minor" => "Maintenance / Facilities"),
            "2808" => array("major" => "Management", "minor" => "Manufacturing / Plant"),
            "2809" => array("major" => "Management", "minor" => "Marketing"),
            "2810" => array("major" => "Management", "minor" => "Non-Profit"),
            "2811" => array("major" => "Management", "minor" => "Purchasing"),
            "2812" => array("major" => "Management", "minor" => "R & D"),
            "2813" => array("major" => "Management", "minor" => "Retail / Store"),
            "2814" => array("major" => "Management", "minor" => "Sales"),
            "2815" => array("major" => "Management", "minor" => "Service"),
            "2816" => array("major" => "Management", "minor" => "Supply / Material"),
            "2817" => array("major" => "Management", "minor" => "Transportation"),
            "2818" => array("major" => "Management", "minor" => "Utilities"),
            "2819" => array("major" => "Management", "minor" => "Operations"),
            "2900" => array("major" => "Senior Management", "minor" => "Senior Management - General"),
            "2901" => array("major" => "Senior Management", "minor" => "President / CEO"),
            "2902" => array("major" => "Senior Management", "minor" => "University Administration / Management"),
            "2903" => array("major" => "Senior Management", "minor" => "VP - Administration"),
            "2904" => array("major" => "Senior Management", "minor" => "VP - Chief Operation Officer / COO"),
            "2905" => array("major" => "Senior Management", "minor" => "VP - Distribution"),
            "2906" => array("major" => "Senior Management", "minor" => "VP - Engineering / Development"),
            "2907" => array("major" => "Senior Management", "minor" => "VP - Finance / CFO"),
            "2908" => array("major" => "Senior Management", "minor" => "VP - Human Resources"),
            "2909" => array("major" => "Senior Management", "minor" => "VP - Information Systems / CIO"),
            "2910" => array("major" => "Senior Management", "minor" => "VP - Manufacturing"),
            "2911" => array("major" => "Senior Management", "minor" => "VP - Marketing / Sales"),
            "2912" => array("major" => "Senior Management", "minor" => "VP - Operations"),
            "3000" => array("major" => "Professional", "minor" => "Professional - General"),
            "3001" => array("major" => "Professional", "minor" => "Architect"),
            "3002" => array("major" => "Professional", "minor" => "Biologist"),
            "3003" => array("major" => "Professional", "minor" => "Chemist"),
            "3004" => array("major" => "Professional", "minor" => "Clergy"),
            "3005" => array("major" => "Professional", "minor" => "Counselor"),
            "3006" => array("major" => "Professional", "minor" => "Economist"),
            "3007" => array("major" => "Professional", "minor" => "Geologist"),
            "3008" => array("major" => "Professional", "minor" => "Hydrologist"),
            "3009" => array("major" => "Professional", "minor" => "Legal - Attorney"),
            "3010" => array("major" => "Professional", "minor" => "Legal - Paralegal"),
            "3011" => array("major" => "Professional", "minor" => "Librarian"),
            "3012" => array("major" => "Professional", "minor" => "Physicist"),
            "3013" => array("major" => "Professional", "minor" => "Psychologist"),
            "3014" => array("major" => "Professional", "minor" => "Safety"),
            "3015" => array("major" => "Professional", "minor" => "Scientist"),
            "3016" => array("major" => "Professional", "minor" => "Social Worker"),
            "3017" => array("major" => "Professional", "minor" => "Teacher"),
            "3018" => array("major" => "Professional", "minor" => "Veterinarian"),
            "3100" => array("major" => "Sales & Marketing", "minor" => "Sales & Marketing - General"),
            "3101" => array("major" => "Sales & Marketing", "minor" => "Advertising"),
            "3102" => array("major" => "Sales & Marketing", "minor" => "Brand / Product Management"),
            "3103" => array("major" => "Sales & Marketing", "minor" => "Marketing Analyst"),
            "3104" => array("major" => "Sales & Marketing", "minor" => "Marketing Research"),
            "3105" => array("major" => "Sales & Marketing", "minor" => "Media Planner / Buyer"),
            "3106" => array("major" => "Sales & Marketing", "minor" => "Public Relations"),
            "3107" => array("major" => "Sales & Marketing", "minor" => "Real Estate"),
            "3108" => array("major" => "Sales & Marketing", "minor" => "Retail"),
            "3109" => array("major" => "Sales & Marketing", "minor" => "Sales - Inside"),
            "3110" => array("major" => "Sales & Marketing", "minor" => "Sales - Medical"),
            "3111" => array("major" => "Sales & Marketing", "minor" => "Sales - Outside"),
            "3112" => array("major" => "Sales & Marketing", "minor" => "Sales - Pharmaceutical"),
            "3113" => array("major" => "Sales & Marketing", "minor" => "Sales - Securities"),
            "3114" => array("major" => "Sales & Marketing", "minor" => "Sales - Technical"),
            "3115" => array("major" => "Sales & Marketing", "minor" => "Sales - Telemarketing"),
            "3116" => array("major" => "Sales & Marketing", "minor" => "Account Management"),
            "3200" => array("major" => "Telecommunications", "minor" => "Telecommunications - General"),
            "3201" => array("major" => "Telecommunications", "minor" => "CLEC / ILEC / IXC"),
            "3202" => array("major" => "Telecommunications", "minor" => "Cable"),
            "3203" => array("major" => "Telecommunications", "minor" => "Consulting"),
            "3204" => array("major" => "Telecommunications", "minor" => "Cellular / PCS / Paging"),
            "3205" => array("major" => "Telecommunications", "minor" => "Data Networking"),
            "3206" => array("major" => "Telecommunications", "minor" => "Fiber Optics"),
            "3207" => array("major" => "Telecommunications", "minor" => "Hardware"),
            "3208" => array("major" => "Telecommunications", "minor" => "IP Telephony"),
            "3209" => array("major" => "Telecommunications", "minor" => "ISP"),
            "3210" => array("major" => "Telecommunications", "minor" => "Integrator"),
            "3211" => array("major" => "Telecommunications", "minor" => "Inter-Connect (CPE)"),
            "3212" => array("major" => "Telecommunications", "minor" => "RBOC"),
            "3213" => array("major" => "Telecommunications", "minor" => "Sales"),
            "3214" => array("major" => "Telecommunications", "minor" => "Satellite"),
            "3215" => array("major" => "Telecommunications", "minor" => "Software"),
            "3216" => array("major" => "Telecommunications", "minor" => "Web Hosting"),
            "3300" => array("major" => "Entertainment", "minor" => "Entertainment - General"),
            "3301" => array("major" => "Entertainment", "minor" => "Accounting/Finance"),
            "3302" => array("major" => "Entertainment", "minor" => "Animation"),
            "3303" => array("major" => "Entertainment", "minor" => "Casting"),
            "3304" => array("major" => "Entertainment", "minor" => "Creative"),
            "3305" => array("major" => "Entertainment", "minor" => "DVD Production"),
            "3306" => array("major" => "Entertainment", "minor" => "Music"),
            "3307" => array("major" => "Entertainment", "minor" => "Post Production"),
            "3308" => array("major" => "Entertainment", "minor" => "Production"),
            "3309" => array("major" => "Entertainment", "minor" => "Publicity"),
            "3310" => array("major" => "Entertainment", "minor" => "Research"),
            "3311" => array("major" => "Entertainment", "minor" => "Technology"),
            "3312" => array("major" => "Entertainment", "minor" => "Theatrical and Stage Production"),
            "3400" => array("major" => "Physician", "minor" => "Physician General"),
            "3401" => array("major" => "Physician", "minor" => "Allergy-Immunology"),
            "3402" => array("major" => "Physician", "minor" => "Ambulatory MedicineLL"),
            "3403" => array("major" => "Physician", "minor" => "Anesthesiology"),
            "3404" => array("major" => "Physician", "minor" => "Audiology"),
            "3405" => array("major" => "Physician", "minor" => "Cardiology"),
            "3406" => array("major" => "Physician", "minor" => "Cardiology Invasive"),
            "3407" => array("major" => "Physician", "minor" => "Cardiology Noninvasive"),
            "3408" => array("major" => "Physician", "minor" => "Cardiothoracic Surgery"),
            "3409" => array("major" => "Physician", "minor" => "Cardiovascular Surgery"),
            "3410" => array("major" => "Physician", "minor" => "Critical Care"),
            "3411" => array("major" => "Physician", "minor" => "Dermatology"),
            "3412" => array("major" => "Physician", "minor" => "Director"),
            "3413" => array("major" => "Physician", "minor" => "Emergency"),
            "3414" => array("major" => "Physician", "minor" => "Endocrinology"),
            "3415" => array("major" => "Physician", "minor" => "Family Practice"),
            "3416" => array("major" => "Physician", "minor" => "Fellowship Programs"),
            "3417" => array("major" => "Physician", "minor" => "Gastroenterology"),
            "3418" => array("major" => "Physician", "minor" => "General Practice"),
            "3419" => array("major" => "Physician", "minor" => "Genetics"),
            "3420" => array("major" => "Physician", "minor" => "Geriatrics"),
            "3421" => array("major" => "Physician", "minor" => "Gynecology/Oncology"),
            "3422" => array("major" => "Physician", "minor" => "Gynecology"),
            "3423" => array("major" => "Physician", "minor" => "Hematology-Oncology"),
            "3424" => array("major" => "Physician", "minor" => "Hospital Administration"),
            "3425" => array("major" => "Physician", "minor" => "Hospitalist"),
            "3426" => array("major" => "Physician", "minor" => "Infectious Disease"),
            "3427" => array("major" => "Physician", "minor" => "Internal Medicine"),
            "3428" => array("major" => "Physician", "minor" => "Locum Tenens"),
            "3429" => array("major" => "Physician", "minor" => "Med/Ped (IM/Ped)"),
            "3430" => array("major" => "Physician", "minor" => "Neonatology"),
            "3431" => array("major" => "Physician", "minor" => "Nephrology"),
            "3432" => array("major" => "Physician", "minor" => "Neurology"),
            "3433" => array("major" => "Physician", "minor" => "Neurophysiology"),
            "3434" => array("major" => "Physician", "minor" => "Neurosurgery"),
            "3435" => array("major" => "Physician", "minor" => "OB/GYN"),
            "3436" => array("major" => "Physician", "minor" => "Occupational Medicine"),
            "3437" => array("major" => "Physician", "minor" => "Oncology"),
            "3438" => array("major" => "Physician", "minor" => "Ophthalmology"),
            "3439" => array("major" => "Physician", "minor" => "Oral Surgery"),
            "3440" => array("major" => "Physician", "minor" => "Orthopaedic"),
            "3441" => array("major" => "Physician", "minor" => "Otolaryngology"),
            "3442" => array("major" => "Physician", "minor" => "Pain Management"),
            "3443" => array("major" => "Physician", "minor" => "Pathology"),
            "3444" => array("major" => "Physician", "minor" => "Pediatric"),
            "3445" => array("major" => "Physician", "minor" => "Pediatric Surgery"),
            "3446" => array("major" => "Physician", "minor" => "Perinatology-Maternal Fetal"),
            "3447" => array("major" => "Physician", "minor" => "Pharmaceutical Industry"),
            "3448" => array("major" => "Physician", "minor" => "Pharmacology"),
            "3449" => array("major" => "Physician", "minor" => "Physiatry"),
            "3450" => array("major" => "Physician", "minor" => "Physician Executive"),
            "3451" => array("major" => "Physician", "minor" => "Plastic Surgery"),
            "3452" => array("major" => "Physician", "minor" => "Podiatry"),
            "3453" => array("major" => "Physician", "minor" => "Primary Care"),
            "3454" => array("major" => "Physician", "minor" => "Psychiatry"),
            "3455" => array("major" => "Physician", "minor" => "Psychiatry Adult"),
            "3456" => array("major" => "Physician", "minor" => "Psychiatry Child/Adolescent"),
            "3457" => array("major" => "Physician", "minor" => "Psychology"),
            "3458" => array("major" => "Physician", "minor" => "Public Health"),
            "3459" => array("major" => "Physician", "minor" => "Pulmonology"),
            "3460" => array("major" => "Physician", "minor" => "Radiation oncology"),
            "3461" => array("major" => "Physician", "minor" => "Radiology"),
            "3462" => array("major" => "Physician", "minor" => "Research"),
            "3463" => array("major" => "Physician", "minor" => "Residency Programs"),
            "3464" => array("major" => "Physician", "minor" => "Rheumatology"),
            "3465" => array("major" => "Physician", "minor" => "Sports Medicine"),
            "3466" => array("major" => "Physician", "minor" => "Surgery"),
            "3467" => array("major" => "Physician", "minor" => "Surgical Oncology"),
            "3468" => array("major" => "Physician", "minor" => "Thoracic Surgery"),
            "3469" => array("major" => "Physician", "minor" => "Trauma Surgery"),
            "3470" => array("major" => "Physician", "minor" => "Urgent Care"),
            "3471" => array("major" => "Physician", "minor" => "Urology"),
            "3472" => array("major" => "Physician", "minor" => "Vascular Surgery")
        );
        $code = $code . "";
        if(!isset($category_map[$code])) return "";
        return $category_map[$code][$col];
    }

}
