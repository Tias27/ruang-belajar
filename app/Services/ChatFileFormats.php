<?php

namespace App\Services;

class ChatFileFormats
{
    public const TEXT_FORMATS = [
        'txt', 'py', 'js', 'mjs', 'cjs', 'ts', 'jsx', 'tsx', 'php', 'html', 'htm',
        'css', 'scss', 'sass', 'less', 'json', 'xml', 'yaml', 'yml', 'toml', 'ini',
        'sql', 'sh', 'bash', 'zsh', 'ps1', 'bat', 'cmd', 'c', 'h', 'cpp', 'hpp',
        'cc', 'cs', 'java', 'kt', 'kts', 'go', 'rs', 'rb', 'swift', 'dart', 'lua',
        'r', 'pl', 'scala', 'vue', 'svelte', 'astro', 'mdx', 'tex', 'graphql',
        'gql', 'proto', 'dockerfile', 'makefile', 'ipynb',
    ];

    private const REQUEST_FORMATS = [
        'pdf' => ['pdf'],
        'docx' => ['word', 'docx', 'doc'],
        'md' => ['markdown', 'md'],
        'csv' => ['csv', 'excel', 'xlsx', 'spreadsheet', 'lembar kerja'],
        'py' => ['python', 'py'],
        'js' => ['javascript', 'js'],
        'mjs' => ['mjs'],
        'cjs' => ['cjs'],
        'ts' => ['typescript', 'ts'],
        'jsx' => ['jsx'],
        'tsx' => ['tsx'],
        'php' => ['php'],
        'html' => ['html', 'htm'],
        'css' => ['css'],
        'scss' => ['scss'],
        'sass' => ['sass'],
        'less' => ['less'],
        'json' => ['json'],
        'xml' => ['xml'],
        'yaml' => ['yaml', 'yml'],
        'toml' => ['toml'],
        'ini' => ['ini'],
        'sql' => ['sql'],
        'sh' => ['shell script', 'sh'],
        'bash' => ['bash'],
        'zsh' => ['zsh'],
        'ps1' => ['powershell', 'ps1'],
        'bat' => ['bat'],
        'cmd' => ['cmd'],
        'c' => ['bahasa c', 'file c'],
        'cpp' => ['c++', 'cpp'],
        'cs' => ['c#', 'csharp'],
        'java' => ['java'],
        'kt' => ['kotlin', 'kt'],
        'go' => ['golang', 'go'],
        'rs' => ['rust', 'rs'],
        'rb' => ['ruby', 'rb'],
        'swift' => ['swift'],
        'dart' => ['dart'],
        'lua' => ['lua'],
        'r' => ['bahasa r', 'file r'],
        'pl' => ['perl', 'pl'],
        'scala' => ['scala'],
        'vue' => ['vue'],
        'svelte' => ['svelte'],
        'astro' => ['astro'],
        'mdx' => ['mdx'],
        'tex' => ['latex', 'tex'],
        'graphql' => ['graphql'],
        'gql' => ['gql'],
        'proto' => ['protobuf', 'proto'],
        'dockerfile' => ['dockerfile'],
        'makefile' => ['makefile'],
        'ipynb' => ['jupyter notebook', 'ipynb'],
    ];

    public static function supported(): array
    {
        return ['pdf', 'docx', 'md', 'csv', ...self::TEXT_FORMATS];
    }

    public static function requestedFormats(string $question): array
    {
        $matches = [];
        foreach (self::REQUEST_FORMATS as $format => $aliases) {
            foreach ($aliases as $alias) {
                if (preg_match('/(?<![\pL\pN])'.preg_quote($alias, '/').'(?![\pL\pN])/iu', $question, $match, PREG_OFFSET_CAPTURE)) {
                    $matches[] = ['format' => $format, 'position' => $match[0][1]];
                    break;
                }
            }
        }

        if (preg_match_all('/\.('.implode('|', array_map('preg_quote', self::supported())).')\b/i', $question, $extensions, PREG_OFFSET_CAPTURE)) {
            foreach ($extensions[1] as [$extension, $position]) {
                $matches[] = ['format' => strtolower($extension), 'position' => $position];
            }
        }

        usort($matches, fn (array $a, array $b) => $a['position'] <=> $b['position']);
        $formats = [];
        foreach ($matches as $match) {
            if (! in_array($match['format'], $formats, true)) {
                $formats[] = $match['format'];
            }
        }

        return $formats;
    }

    public static function label(string $format): string
    {
        return match ($format) {
            'pdf' => 'PDF',
            'docx' => 'Word',
            'md' => 'Markdown',
            'csv' => 'CSV',
            'py' => 'Python',
            'js', 'mjs', 'cjs' => 'JavaScript',
            'ts', 'tsx' => 'TypeScript',
            'jsx' => 'React (JSX)',
            'php' => 'PHP',
            'html', 'htm' => 'HTML',
            'css', 'scss', 'sass', 'less' => strtoupper($format),
            'json' => 'JSON',
            'sql' => 'SQL',
            'java' => 'Java',
            'cpp' => 'C++',
            'cs' => 'C#',
            'go' => 'Go',
            'rs' => 'Rust',
            'rb' => 'Ruby',
            'kt', 'kts' => 'Kotlin',
            default => strtoupper($format),
        };
    }
}
