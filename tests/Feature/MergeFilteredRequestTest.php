<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use InvalidArgumentException;
use Tests\TestCase;

class MergeFilteredRequestTest extends TestCase
{
    public function test_merge_filtered_cleans_declared_string_fields(): void
    {
        $dirty = '  Hello  '.json_decode('"\u200B"').'  ';
        $request = Request::create('/', 'POST', [
            'name' => $dirty,
            'bio' => '<b>bio</b>',
            'count' => 3,
        ]);

        $request->mergeFiltered([
            'name' => 'safe',
            'bio' => 'safe',
        ]);

        $this->assertSame('Hello', $request->input('name'));
        $this->assertSame('bio', $request->input('bio'));
        $this->assertSame(3, $request->input('count'));
    }

    public function test_merge_filtered_leaves_invalid_utf8_and_non_strings_alone(): void
    {
        $request = Request::create('/', 'POST', [
            'name' => "\xFF",
            'meta' => ['note' => 123],
        ]);

        $request->mergeFiltered(['name' => 'safe', 'meta.note' => 'safe']);

        $this->assertSame("\xFF", $request->input('name'));
        $this->assertSame(123, $request->input('meta.note'));
    }

    public function test_merge_filtered_supports_wildcard_nested_fields(): void
    {
        $request = Request::create('/', 'POST', [
            'items' => [
                ['name' => '  A  '],
                ['name' => '  B  '],
            ],
            'untouched' => '  X  ',
        ]);

        $request->mergeFiltered(['items.*.name' => 'safe']);

        $this->assertSame('A', $request->input('items.0.name'));
        $this->assertSame('B', $request->input('items.1.name'));
        $this->assertSame('  X  ', $request->input('untouched'));
    }

    public function test_unknown_preset_throws_when_merging(): void
    {
        $request = Request::create('/', 'POST', ['name' => 'hello']);

        $this->expectException(InvalidArgumentException::class);
        $request->mergeFiltered(['name' => 'does-not-exist']);
    }
}
