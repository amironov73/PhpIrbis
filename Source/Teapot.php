<?php

/** @noinspection PhpUnused */

namespace Irbis;

require_once 'Search.php';

static $_stopWords = array(
    'a', 'about', 'after', 'against', 'all', 'als', 'an', 'and', 'as', 'at', 'auf', 'aus', 'aux', 'b', 'by', 'c', 'd',
    'da','dans', 'das', 'de', 'der', 'des', 'di', 'die', 'do', 'du', 'e', 'ein', 'eine', 'einen', 'eines', 'einer',
    'el', 'en', 'et', 'f', 'for', 'from', 'fur', 'g', 'h', 'i', 'ihr', 'ihre', 'im', 'in', 'into', 'its', 'j', 'k',
    'l', 'la', 'las', 'le', 'les', 'los', 'm', 'mit', 'mot', 'n', 'near', 'non', 'not', 'o', 'of', 'on', 'or', 'over',
    'out', 'p', 'par', 'para', 'qui', 'r', 's', 'some', 'sur', 't', 'the', 'their', 'through', 'till', 'to', 'u',
    'uber', 'und', 'under', 'upon', 'used', 'using', 'v', 'van', 'w', 'when', 'with', 'x', 'y', 'your','z', 'а',
    'ая', 'б', 'без', 'бы', 'в', 'вблизи', 'вдоль','во', 'вокруг', 'всех', 'г', 'го', 'д', 'для', 'до', 'е','его',
    'ее', 'ж', 'же', 'з', 'за', 'и', 'из', 'или', 'им', 'ими', 'их', 'к', 'как', 'ко', 'кое', 'л', 'летию', 'ли',
    'м', 'между', 'млн', 'н', 'на', 'над', 'не', 'него', 'ним', 'них', 'о', 'об', 'от', 'п', 'по', 'под', 'после',
    'при', 'р', 'с', 'со', 'т', 'та', 'так', 'такой', 'также', 'то', 'тоже', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ы',
    'ые', 'ый', 'э', 'этих', 'этой', 'ю', 'я',
);

/**
 * Проверка, является ли заданный текст стоп-словом.
 *
 * @param $text string Текст для проверки.
 * @return bool Результат.
 */
function isStopWord($text)
{
    global $_stopWords;

    if (!$text) {
        return true;
    }

    foreach ($_stopWords as $word) {
        if (!strcasecmp($word, $text)) {
            return true;
        }
    }

    return false;
}

/**
 * Коэффициент релевантности.
 */
final class RelevanceCoefficient
{
    /**
     * @var array Массив меток полей, для которых действует данный коэффициент.
     */
    public $fields;

    /**
     * @var double Значение коэффициента.
     */
    public $value;

    /**
     * Конструктор.
     */
    public function __construct($value, $tags)
    {
        $this->fields = $tags;
        $this->value = $value;
    }
}

/**
 * Оценщик релевантности найденных библиографических записей.
 */
final class RelevanceEvaluator
{
    /**
     * @var array Массив коэффициентов.
     */
    public $coefficients;

    /**
     * @var string Поисковый запрос.
     */
    public $searchExpression;

    /**
     * @var array Массив терминов, на которые разбивается поисковый запрос.
     */
    public $terms;

    /**
     * Оценка реалеватности записи.
     *
     * @param $record MarcRecord Запись, подлежащая оwtyrt
     * @return double
     */
    public function evaluate($record)
    {
        return 0.0;
    }
}

/**
 * Простой поиск "для чайников".
 */
final class Teapot
{
    /**
     * @var array Массив префиксов для терминов.
     */
    public $prefixes;

    /**
     * @var string Суффикс для терминов.
     */
    public $suffix;

    /**
     * Конструктор.
     */
    public function __construct()
    {
        $this->prefixes = array('A=', 'T=', 'M=', 'K=');
        $this->suffix = '$';
    }

    public function buildSearchExpression($query)
    {
        if (!$query) {
            return '';
        }

        $query = trim($query);
        if (!$query) {
            return '';
        }

        $terms = array();
        $terms[$query] = 1;
        preg_match_all('/\w+/u', $query, $words);
        foreach ($words[0] as $word) {
            $terms[$word] = 1;
        }

        $result = '';
        $first = true;
        foreach ($terms as $term => $k) {
            if (isStopWord($term)) {
                continue;
            }

            foreach ($this->prefixes as $prefix) {
                if (!$first) {
                    $result .= ' + ';
                }

                $result .= Search::wrapIfNeeded($prefix . $term . $this->suffix);

                $first = false;
            }
        }

        return $result;
    }
}
