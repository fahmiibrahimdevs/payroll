<?php

namespace App\Console\Commands;

use App\Models\CompanyProfile;
use App\Services\CompanyProfileService;
use Illuminate\Console\Command;

class SyncEmployeeEmailDomainCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'employees:sync-email-domain {domain? : The email domain to set, e.g. percetakansubang.com}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all employee emails and user logins to the active company email domain';

    /**
     * Execute the console command.
     */
    public function handle(CompanyProfileService $companyProfileService): int
    {
        $domainArg = $this->argument('domain');

        if (! empty($domainArg)) {
            $domain = ltrim(trim($domainArg), '@');
            $companyProfileService->updateProfile(['email_domain' => $domain]);
            $this->info("Updated company profile email_domain to: @{$domain}");
            $this->info("All employee and user account emails have been synced to @{$domain}.");
        } else {
            $domain = CompanyProfile::getProfile()->getEmailDomain();
            $this->info("Using current company email domain: @{$domain}");
            $count = $companyProfileService->syncEmployeeEmailDomains($domain);
            $this->info("Successfully synced {$count} employee and user account email(s) to @{$domain}.");
        }

        return self::SUCCESS;
    }
}
