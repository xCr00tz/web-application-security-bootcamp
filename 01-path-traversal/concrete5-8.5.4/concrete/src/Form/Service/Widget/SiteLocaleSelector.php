<?php

namespace Concrete\Core\Form\Service\Widget;

use Concrete\Core\Entity\Site\Locale;
use Concrete\Core\Entity\Site\Site;
use Concrete\Core\Multilingual\Service\UserInterface\Flag;
use Concrete\Core\Utility\Service\Identifier;
use View;

class SiteLocaleSelector
{
    /**
     * Creates form fields and JavaScript page chooser for choosing a locale.
     *
     * @param string $fieldName
     * @param \Concrete\Core\Entity\Site\Site $site
     * @param \Concrete\Core\Entity\Site\Locale|null $selectedLocale
     * @param array $options Supported options are:
     *     bool $allowNull Set to a non falsy value to allow users to choose "no" locale [default: false]
     *     string $noLocaleText The string to represent "no locale" [default: t('No Locale')]
     *     bool|string $displayLocaleCode Set to 'auto' to automatically determine it; set to a non falsy value to display the locale ID [default: 'auto']
     *
     * @return string
     */
    public function selectLocale($fieldName, Site $site, Locale $selectedLocale = null, array $options = [])
    {
        $v = View::getInstance();
        $v->requireAsset('core/app');

        $siteLocales = $site->getLocales()->toArray();

        $allowNull = !empty($options['allowNull']);
        $nullText = isset($options['noLocaleText']) ? $options['noLocaleText'] : t('No Locale');
        $displayLocaleCode = $this->shouldDisplayLocaleCode($options, $siteLocales);

        if ($selectedLocale === null && !$allowNull) {
            $selectedLocale = $site->getDefaultLocale();
        }

        $localeID = $selectedLocale ? $selectedLocale->getLocaleID() : '';

        $identifier = (new Identifier())->getString(32);

        $flag = $selectedLocale ? Flag::getLocaleFlagIcon($selectedLocale) : '';

        if ($selectedLocale) {
            $label = h($selectedLocale->getLanguageText());
            if ($displayLocaleCode) {
                $label .= ' <span class="text-muted small">' . h($selectedLocale->getLocale()) . '</span>';
            }
        } else {
            $label = $nullText;
        }

        $localeHTML = '';
        if ($allowNull) {
            $localeHTML .= '<li><a href="#"' . ($selectedLocale === null ? ' data-locale="default"' : '') . ' data-select-locale="">' . $nullText . '</li>';
        }
        foreach ($siteLocales as $locale) {
            $localeHTML .= '<li><a href="#" ';
            if ($selectedLocale && $selectedLocale->getLocaleID() == $locale->getLocaleID()) {
                $localeHTML .= 'data-locale="default"';
            }
            $localeHTML .= 'data-select-locale="' . $locale->getLocaleID() . '">';
            $localeHTML .= Flag::getLocaleFlagIcon($locale) . ' ' . $locale->getLanguageText();
            if ($displayLocaleCode) {
                $localeHTML .= ' <span class="text-muted small">' . h($locale->getLocale()) . '</span>';
            }
            $localeHTML .= '</a></li>';
        }

        $html = <<<EOL
        <input type="hidden" name="{$fieldName}" value="{$localeID}">
        <div class="btn-group" data-locale-selector="{$identifier}">
            <button type="button" class="btn btn-default" data-toggle="dropdown">
                {$flag} {$label}
            <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" role="menu">
                {$localeHTML}
            </ul>
        </div>
        <script type="text/javascript">
            $(function() {
                $('[data-toggle=dropdown]').dropdown();
                $('div[data-locale-selector={$identifier}]').on('click', 'a[data-select-locale]', function(e) {
                    e.preventDefault();
                    var localeID = $(this).attr('data-select-locale'),
                        html = $(this).html() + ' <span class="caret"></span>',
                        form = $(this).closest('form');

                    form.find('input[name={$fieldName}]').val(localeID);
                    form.find('div[data-locale-selector={$identifier}] > button').html(html);
                });
            });
        </script>

EOL;

        return $html;
    }

    /**
     * Creates form fields and JavaScript page chooser for choosing a locale.
     *
     * @param string $fieldName
     * @param \Concrete\Core\Entity\Site\Site $site
     * @param \Concrete\Core\Entity\Site\Locale[]|string[]|int[] $selectedLocales
     * @param array $options Supported options are:
     *     string $noLocaleText The string to represent "no locale" [default: t('No Locale')]
     *     bool|string $displayLocaleCode Set to 'auto' to automatically determine it; set to a non falsy value to display the locale ID [default: 'auto']
     *
     * @return string
     */
    public function selectLocaleMultiple($fieldName, Site $site, array $selectedLocales = [], array $options = [])
    {
        $v = View::getInstance();
        $v->requireAsset('selectize');

        $siteLocales = $site->getLocales()->toArray();
        $displayLocaleCode = $this->shouldDisplayLocaleCode($options, $siteLocales);

        $actuallySelectedLocales = [];
        foreach ($selectedLocales as $selectedLocale) {
            $localeID = null;
            $localeCode = null;
            if ($selectedLocale instanceof Locale) {
                $localeID = $selectedLocale->getLocaleID();
            } elseif (is_numeric($selectedLocale)) {
                $localeID = (int) $selectedLocale;
            } else {
                $localeCode = (string) $localeCode;
            }
            foreach ($siteLocales as $siteLocale) {
                if ($localeID === $siteLocale->getLocaleID() || $localeCode === $siteLocale->getLocale()) {
                    $actuallySelectedLocales[] = $siteLocale;
                }
            }
        }
        $htmlOptions = '';
        foreach ($siteLocales as $siteLocale) {
            $htmlOptions .= '<option';
            $htmlOptions .= ' data-locale="' . h(json_encode([
                'localeID' => $siteLocale->getLocaleID(),
                'localeCode' => $siteLocale->getLocale(),
                'localeName' => $siteLocale->getLanguageText(),
                'localeIcon' => (string) Flag::getLocaleFlagIcon($siteLocale),
            ])) . '"';
            $htmlOptions .= ' value="' . $siteLocale->getLocaleID() . '"';
            if (in_array($siteLocale, $actuallySelectedLocales, true)) {
                $htmlOptions .= ' selected="selected"';
            }
            $htmlOptions .= '>' . h($siteLocale->getLanguageText()) . '</option>';
        }

        $fieldNameHtml = h($fieldName . (substr($fieldName, -2) === '[]' ? '' : '[]'));
        $placeholderJS = json_encode(isset($options['noLocaleText']) ? (string) $options['noLocaleText'] : t('No Locale'));
        $displayLocaleCodeJS = json_encode($displayLocaleCode);

        $identifier = 'ccm-sitelocaleselector-' . trim(preg_replace('/\W+/', '_', $fieldName), '_') . '-' . (new Identifier())->getString(32);
        $identifierHtml = h($identifier);
        $identifierJS = json_encode($identifier);

        return <<<EOL

<select id="{$identifierHtml}" name="{$fieldNameHtml}" multiple="multiple" class="ccm-sitelocaleselector">
    $htmlOptions
</select>
<script type="text/javascript">
$(document).ready(function() {
    var displayLocaleCode = {$displayLocaleCodeJS};
    function render(data, escape) {
        var html = '<div>' + data.localeIcon + ' ' + escape(data.localeName);
        if (displayLocaleCode) {
            html += ' <span class="text-muted small">' + escape(data.localeCode) + '</span>';
        }
        html += '</div>';
        return html;
    }
    $('#' + {$identifierJS}).selectize({
        placeholder: {$placeholderJS},
        plugins: ['remove_button'],
        valueField: 'localeID',
        searchField: displayLocaleCode ? ['localeName', 'localeCode'] : ['localeName'],
        persist: false,
        dataAttr: 'data-locale',
        sortField: [
            {field: 'localeName'},
            {field: 'localeCode'},
        ],
        render: {
            item: render,
            option: render
        }
    });
});
</script>

EOL;
    }

    /**
     * @param array $options
     * @param \Concrete\Core\Entity\Site\Locale[] $locales
     *
     * @return bool
     */
    private function shouldDisplayLocaleCode(array $options, array $locales)
    {
        if (isset($options['displayLocaleCode']) && $options['displayLocaleCode'] !== 'auto') {
            return $options['displayLocaleCode'] ? true : false;
        }

        $names = array_map(
            function (Locale $locale) {
                return $locale->getLanguageText();
            },
            $locales
        );

        return count($names) > count(array_unique($names));
    }
}
