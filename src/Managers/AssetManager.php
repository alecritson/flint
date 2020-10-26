<?php

namespace Flint\Managers;

use Flint\Models\Asset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AssetManager
{
    protected $source;

    protected $asset;

    protected $model;

    protected $folder;

    public function source($handle)
    {
        $this->source = \Flint\Models\AssetSource::where('handle', '=', $handle)->first();
        return $this;
    }

    public function model($model)
    {
        $this->model = $model;
        return $this;
    }

    public function folder($name)
    {
        $this->folder = $name;
        return $this;
    }

    public function attachTempFiles($files = [], $copy = false)
    {
        foreach ($files as $file) {
            $filename = pathinfo($file->path, PATHINFO_FILENAME);
            $asset = new Asset();
            $asset->filename = $filename;
            $asset->kind = $file->extension;
            $asset->size = $file->size;
            $asset->asset_source_id = $this->source->id;

            $this->folder($asset->source->folder);

            try {
                if ($copy) {
                    Storage::copy($file->path, "{$asset->source->type}/{$this->folder}/{$filename}.{$file->extension}");
                } else {
                    Storage::move($file->path, "{$asset->source->type}/{$this->folder}/{$filename}.{$file->extension}");
                }
            } catch (\League\Flysystem\FileExistsException $e) {

            }

            $this->model->assets()->save($asset);
        }
    }

    public function store(UploadedFile $file)
    {
        $asset = new Asset();

        $filename = pathinfo($file->hashName(), PATHINFO_FILENAME);

        $asset->filename = $filename;
        $asset->kind = $file->extension();
        $asset->size = $file->getClientSize();
        $asset->asset_source_id = $this->source->id;

        if (!$this->folder && $asset->source->folder) {
            $this->folder($asset->source->folder);
        }

        $file->store($this->folder, $asset->source->type);

        $this->model->assets()->save($asset);

        return $asset;
    }
}
