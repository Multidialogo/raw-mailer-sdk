<?php

namespace multidialogo\RawMailerSdk;

class ForksCounter
{
    public static function getApproximativelyAailable(): int
    {
        // Get max number of user processes from ulimit
        $ulimitProcesses = (int)trim(shell_exec('ulimit -u'));
        echo "Max user processes (ulimit -u): $ulimitProcesses\n";

        // Get max number of open file descriptors
        $ulimitFiles = (int)trim(shell_exec('ulimit -n'));
        echo "Max open files (ulimit -n): $ulimitFiles\n";

        // Get max PID limit from proc file system (Linux-specific)
        $pidMax = (int)trim(file_get_contents('/proc/sys/kernel/pid_max'));
        echo "Max PID value (pid_max): $pidMax\n";

        // Memory available on the system (Linux-specific)
        $memInfo = file_get_contents('/proc/meminfo');
        preg_match('/MemAvailable:\s+(\d+) kB/', $memInfo, $matches);
        $availableMemoryKB = isset($matches[1]) ? (int)$matches[1] : 0;
        echo "Available memory (kB): $availableMemoryKB kB\n";

        // Total number of available forks depends on the lesser of the ulimit, pid_max, and available system memory.
        // This is a rough estimate since each forked process requires memory and resources.
        // FIXME: save this to file based on average memory peak
        $estimatedForksByMemory = $availableMemoryKB / 20000; // Assume each process uses about 20MB (adjust as necessary)

        return min($ulimitProcesses, $pidMax, $estimatedForksByMemory);
    }
}