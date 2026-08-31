<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\SiteSettings;
use Illuminate\Http\Request;

class SiteSettingController extends Controller
{
    /** Les clés de config contiennent des points → on les remplace dans les noms de champ. */
    private static function fieldName(string $key): string
    {
        return 'f['.str_replace('.', '~', $key).']';
    }

    public function edit()
    {
        $saved = array_keys(SiteSettings::map());

        return view('admin.site-settings.edit', [
            'groups' => config('settings.groups'),
            'valueOf' => fn (array $field) => SiteSettings::value($field),
            'nameOf' => fn (string $key) => self::fieldName($key),
            // Nombre de champs personnalisés (≠ valeur du fichier config) par groupe.
            'overrides' => collect(config('settings.groups'))->map(
                fn ($g) => collect($g['fields'])->pluck('key')->intersect($saved)->count()
            )->all(),
        ]);
    }

    public function update(Request $request)
    {
        $fields = SiteSettings::fields();

        $rules = [];
        $attributes = [];
        foreach ($fields as $field) {
            $name = 'f.'.str_replace('.', '~', $field['key']);
            // `rules` peut être une chaîne « a|b|c » ou déjà un tableau (utile quand une
            // règle regex contient un « | »).
            $rules[$name] = is_array($field['rules'] ?? null)
                ? $field['rules']
                : explode('|', $field['rules'] ?? 'nullable|string|max:400');
            $attributes[$name] = $field['label'];
        }

        $request->validate($rules, [], $attributes);

        $submitted = $request->input('f', []);
        $values = [];
        foreach ($fields as $field) {
            $name = str_replace('.', '~', $field['key']);
            $values[$field['key']] = ($field['type'] ?? 'text') === 'boolean'
                ? (($submitted[$name] ?? null) ? '1' : '0')
                : ($submitted[$name] ?? null);
        }

        SiteSettings::put($values, $request->user()->id);
        SiteSettings::apply(); // reflète les nouvelles valeurs sans attendre le prochain démarrage
        AuditLog::record('site_settings.updated', null, [
            'keys' => array_keys(array_filter($values, fn ($v) => $v !== null && $v !== '')),
        ]);

        return back()->with('status', 'Paramètres du site enregistrés.');
    }
}
