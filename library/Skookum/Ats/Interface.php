<?php
/**
 * An interface for any number of ATSes.
 *
 * @author  Corey Ballou
 */
interface Skookum_Ats_Interface {

    /**
     * Require interfaces to have a contstructor.
     *
     * @access  public
     * @param   QueryPath   $qp
     */
    public function __construct(QueryPath $qp);

    /**
     * Scrape the job listings page.
     *
     * @access  public
     */
    public function scrapeJobListings($jobList, $jobListType = 'XML');

    /**
     * Scrape a job details page.
     *
     * @access  public
     * @param   string  $jobDetailsUrl
     * @param   string  $referrerUrl
     * @param   string  $jobDetailsType
     */
    public function scrapeJobDetails($jobDetailsUrl, $referrerUrl, $jobDetailsType = 'HTML');

}