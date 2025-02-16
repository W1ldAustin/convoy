<?php

namespace Convoy\Http\Requests\Admin\Nodes\Isos;

use Convoy\Models\ISO;
use Convoy\Models\Node;
use Illuminate\Validation\Validator;
use Convoy\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rules\Enum;
use Convoy\Services\Nodes\Isos\IsoService;
use Convoy\Enums\Helpers\ChecksumAlgorithm;

class StoreIsoRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $isoRules = ISO::getRules();

        $rules = [
            'should_download' => 'required|boolean',
            'name' => $isoRules['name'],
            'file_name' => $isoRules['file_name'],
            'hidden' => $isoRules['hidden'],
            'link' => 'required_if:should_download,1|url|max:191|exclude_if:should_download,0',
            'checksum_algorithm' => ['sometimes', new Enum(ChecksumAlgorithm::class), 'exclude_if:should_download,0'],
            'checksum' => 'required_with:checksum_algorithm|string|max:191|exclude_if:should_download,0',
        ];

        return $rules;
    }

    public function after(): array
    {
        $rules = [
            function (Validator $validator) {
                if (ISO::where('file_name', $this->string('file_name'))->exists()) {
                    $validator->errors()->add('file_name', __('validation.unique', ['attribute' => 'file name']));
                }
            }
        ];

        if (!$this->boolean('should_download')) {
            $rules[] = function (Validator $validator) {
                $node = $this->parameter('node', Node::class);

                $iso = app(IsoService::class)->getIso($node, $this->input('file_name'));

                if (is_null($iso)) {
                    $validator->errors()->add('file_name', 'This ISO doesn\'t exist.');
                }
            };
        }

        return $rules;
    }
}
