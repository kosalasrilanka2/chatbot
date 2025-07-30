<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunComprehensiveTests extends Command
{
    protected $signature = 'test:comprehensive {--coverage : Generate coverage report} {--filter= : Filter specific tests}';
    protected $description = 'Run comprehensive tests for both agent and user interfaces';

    public function handle()
    {
        $this->info('🧪 Running Comprehensive Test Suite for Chatbot Application');
        $this->newLine();

        $testSuites = [
            'Agent Dashboard Tests' => 'tests/Feature/AgentDashboardTest.php',
            'User Chat Interface Tests' => 'tests/Feature/UserChatInterfaceTest.php',
            'Skill-Based Assignment Tests' => 'tests/Feature/SkillBasedAssignmentTest.php',
            'Conversation Flow Tests' => 'tests/Feature/ConversationFlowTest.php',
            'Agent Model Unit Tests' => 'tests/Unit/AgentModelTest.php'
        ];

        $filter = $this->option('filter');
        $generateCoverage = $this->option('coverage');

        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;

        foreach ($testSuites as $suiteName => $testPath) {
            if ($filter && !str_contains(strtolower($suiteName), strtolower($filter))) {
                continue;
            }

            $this->info("📋 Running: {$suiteName}");
            $this->info("   File: {$testPath}");

            $command = ['test', $testPath, '--stop-on-failure'];
            
            if ($generateCoverage) {
                $command[] = '--coverage';
            }

            $exitCode = Artisan::call('test', [
                $testPath,
                '--stop-on-failure' => true
            ]);

            if ($exitCode === 0) {
                $this->info("   ✅ PASSED");
                $passedTests++;
            } else {
                $this->error("   ❌ FAILED");
                $this->error(Artisan::output());
                $failedTests++;
            }

            $totalTests++;
            $this->newLine();
        }

        // Summary
        $this->info('📊 Test Summary:');
        $this->info("   Total Test Suites: {$totalTests}");
        $this->info("   Passed: {$passedTests}");
        $this->info("   Failed: {$failedTests}");

        if ($failedTests === 0) {
            $this->info('🎉 All tests passed!');
        } else {
            $this->error("⚠️  {$failedTests} test suite(s) failed.");
        }

        $this->newLine();
        $this->info('🔍 Test Coverage Areas:');
        $this->info('   ✓ Agent dashboard with skill badges display');
        $this->info('   ✓ Agent status management');
        $this->info('   ✓ Agent conversation assignment and management');
        $this->info('   ✓ Agent message sending and receiving');
        $this->info('   ✓ User chat interface functionality');
        $this->info('   ✓ User conversation creation with skill preferences');
        $this->info('   ✓ User message sending and conversation closure');
        $this->info('   ✓ Skill-based auto-assignment (both skills required)');
        $this->info('   ✓ Agent capacity and workload management');
        $this->info('   ✓ Complete conversation flows');
        $this->info('   ✓ Error handling and validation');
        $this->info('   ✓ Concurrent user scenarios');
        $this->info('   ✓ Agent model relationships and methods');

        return $failedTests === 0 ? 0 : 1;
    }
}
