<?php

namespace Concrete\Core\Updater\Migrations\Migrations;

use Concrete\Core\File\File;
use Concrete\Core\Updater\Migrations\AbstractMigration;
use Concrete\Core\Updater\Migrations\RepeatableMigrationInterface;

class Version20170610000000 extends AbstractMigration implements RepeatableMigrationInterface
{
    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Updater\Migrations\AbstractMigration::upgradeDatabase()
     */
    public function upgradeDatabase()
    {
        // Find all files that have no FileVersions as these are invalid and are left over from a bug
        $query = 'SELECT F.fID FROM Files F WHERE F.fID NOT IN (SELECT FV.fID FROM FileVersions FV)';
        $orphan_files = $this->connection->executeQuery($query);
        while ($fID = $orphan_files->fetchColumn()) {
            $f = File::getByID($fID);
            if ($f !== null) {
                $f->delete();
            }
        }
    }
}
