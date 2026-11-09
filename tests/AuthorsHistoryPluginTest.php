<?php

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.security.Role');
import('lib.pkp.classes.user.User');
import('plugins.generic.authorsHistory.AuthorsHistoryPlugin');

class AuthorsHistoryPluginTest extends PKPTestCase
{
    private $contextId = 1;
    private $plugin;

    public function setUp(): void
    {
        parent::setUp();
        $this->plugin = new AuthorsHistoryPlugin();
    }

    private function createUserWithRoles(array $rolesByContext)
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['hasRole'])
            ->getMock();
        $user->method('hasRole')->willReturnCallback(
            function ($roles, $contextId) use ($rolesByContext) {
                $userRoles = $rolesByContext[$contextId] ?? [];
                return count(array_intersect((array) $roles, $userRoles)) > 0;
            }
        );

        return $user;
    }

    public function testAuthorCannotViewAuthorsHistory(): void
    {
        $author = $this->createUserWithRoles([$this->contextId => [ROLE_ID_AUTHOR]]);
        $this->assertFalse($this->plugin->userCanViewAuthorsHistory($author, $this->contextId));
    }

    public function testReviewerAndReaderCannotViewAuthorsHistory(): void
    {
        $user = $this->createUserWithRoles([$this->contextId => [ROLE_ID_REVIEWER, ROLE_ID_READER]]);
        $this->assertFalse($this->plugin->userCanViewAuthorsHistory($user, $this->contextId));
    }

    public function testMissingUserCannotViewAuthorsHistory(): void
    {
        $this->assertFalse($this->plugin->userCanViewAuthorsHistory(null, $this->contextId));
    }

    public function testManagerOfAnotherContextCannotViewAuthorsHistory(): void
    {
        $user = $this->createUserWithRoles([2 => [ROLE_ID_MANAGER], $this->contextId => [ROLE_ID_AUTHOR]]);
        $this->assertFalse($this->plugin->userCanViewAuthorsHistory($user, $this->contextId));
    }

    public function testManagerCanViewAuthorsHistory(): void
    {
        $manager = $this->createUserWithRoles([$this->contextId => [ROLE_ID_MANAGER]]);
        $this->assertTrue($this->plugin->userCanViewAuthorsHistory($manager, $this->contextId));
    }

    public function testSubEditorCanViewAuthorsHistory(): void
    {
        $subEditor = $this->createUserWithRoles([$this->contextId => [ROLE_ID_SUB_EDITOR]]);
        $this->assertTrue($this->plugin->userCanViewAuthorsHistory($subEditor, $this->contextId));
    }

    public function testSiteAdminCanViewAuthorsHistory(): void
    {
        $admin = $this->createUserWithRoles([CONTEXT_SITE => [ROLE_ID_SITE_ADMIN]]);
        $this->assertTrue($this->plugin->userCanViewAuthorsHistory($admin, $this->contextId));
    }
}
