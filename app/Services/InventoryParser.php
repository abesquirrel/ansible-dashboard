<?php

namespace App\Services;

class InventoryParser
{
    /**
     * Parse inventory list array and return groups, hosts, hostGroups mapping, hostvars, and errors.
     *
     * @param array $list
     * @return array
     */
    public function parseList(array $list): array
    {
        if (isset($list['error'])) {
            return [
                'groups' => [],
                'hosts' => [],
                'hostGroups' => [],
                'hostvars' => [],
                'error' => $list['error'],
            ];
        }

        $hostvars = $list['_meta']['hostvars'] ?? [];
        $groupsWithResolvedHosts = [];
        $hostGroups = [];

        foreach ($list as $groupName => $groupData) {
            if ($groupName === '_meta' || $groupName === 'all') {
                continue;
            }
            $resolvedHosts = $this->getHostsForGroup($groupName, $list);
            $groupsWithResolvedHosts[$groupName] = [
                'hosts' => $resolvedHosts,
            ];

            foreach ($resolvedHosts as $host) {
                $hostGroups[$host][] = $groupName;
            }
        }

        // Gather all unique hosts (including from all, ungrouped, etc.)
        $allHosts = [];
        if (isset($list['all']['hosts'])) {
            $allHosts = array_merge($allHosts, $list['all']['hosts']);
        }
        foreach ($hostGroups as $host => $groups) {
            $allHosts[] = $host;
            $hostGroups[$host] = array_values(array_unique($groups));
        }
        foreach (array_keys($hostvars) as $host) {
            $allHosts[] = $host;
        }
        $allUniqueHosts = array_values(array_unique($allHosts));

        // Ensure every host has an entry in hostGroups
        foreach ($allUniqueHosts as $host) {
            if (!isset($hostGroups[$host])) {
                $hostGroups[$host] = [];
            }
        }

        return [
            'groups' => $groupsWithResolvedHosts,
            'hosts' => $allUniqueHosts,
            'hostGroups' => $hostGroups,
            'hostvars' => $hostvars,
            'error' => null,
        ];
    }

    /**
     * Recursively find all hosts for a group and its children.
     */
    private function getHostsForGroup(string $groupName, array $list, array &$visited = []): array
    {
        if (in_array($groupName, $visited)) {
            return [];
        }
        $visited[] = $groupName;

        $hosts = [];
        if (isset($list[$groupName]['hosts']) && is_array($list[$groupName]['hosts'])) {
            $hosts = $list[$groupName]['hosts'];
        }

        if (isset($list[$groupName]['children']) && is_array($list[$groupName]['children'])) {
            foreach ($list[$groupName]['children'] as $childGroup) {
                $hosts = array_merge($hosts, $this->getHostsForGroup($childGroup, $list, $visited));
            }
        }

        return array_unique($hosts);
    }
}