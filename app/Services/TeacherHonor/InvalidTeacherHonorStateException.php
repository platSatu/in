<?php

namespace App\Services\TeacherHonor;

use RuntimeException;

/**
 * Dilempar TeacherHonorService kalau ada yang mencoba transisi status yang
 * tidak valid dari kondisi TeacherHonor saat ini (mis. markPaid() padahal
 * belum di-approve Manager).
 */
class InvalidTeacherHonorStateException extends RuntimeException
{
}
