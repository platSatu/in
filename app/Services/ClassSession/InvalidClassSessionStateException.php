<?php

namespace App\Services\ClassSession;

use RuntimeException;

/**
 * Dilempar ClassSessionWorkflowService kalau ada yang mencoba melakukan
 * transisi status yang tidak valid dari kondisi ClassSession saat ini
 * (mis. admin approve padahal statusnya masih 'menunggu_guru', atau guru
 * approve pengajuan yang sudah 'ditolak_admin').
 */
class InvalidClassSessionStateException extends RuntimeException
{
}
