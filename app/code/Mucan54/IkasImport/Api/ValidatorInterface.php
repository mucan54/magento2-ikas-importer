<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Api;

use Mucan54\IkasImport\Api\Data\ValidationResultInterface;

/**
 * Validator interface
 *
 * Validates data according to business rules
 *
 * @api
 */
interface ValidatorInterface
{
    /**
     * Validate data
     *
     * @param mixed $data
     * @return ValidationResultInterface
     */
    public function validate($data): ValidationResultInterface;

    /**
     * Get validation rules
     *
     * @return array
     */
    public function getRules(): array;
}
