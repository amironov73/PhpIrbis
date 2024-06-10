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
     * @var bool Приоритетная метка поля (если найдена хотя бы одна запись, поиск завершается).
     */
    public $priority;

    /**
     * Конструктор.
     */
    public function __construct($value, $priority, $tags)
    {
        $this->fields = $tags;
        $this->priority = $priority;
        $this->value = $value;
    }
}

/**
 * Настройки для оценки релевантности.
 */
final class RelevanceSettings
{
    /**
     * @var array Массив коэффициентов релевантности.
     */
    public $coefficients;

    /**
     * @var double Релевантность для упоминаний в посторонних полях.
     */
    public $extraneous;

    /**
     * @var double Мультипликатор для случая полного совпадения.
     */
    public $multiplier;

    /**
     * Конструктор.
     */
    public function __construct()
    {
        $this->coefficients = array();
        $this->extraneous = 1.0;
        $this->multiplier = 2.0;
    }

    /**
     * @return RelevanceSettings Настройки по умолчанию для базы IBIS.
     */
    public static function forIbis()
    {
        $result = new RelevanceSettings();
        $result->extraneous = 1.0;
        $result->multiplier = 2.0;
        $result->coefficients = [

            // заглавие
            new RelevanceCoefficient(20, true,
                [
                    200, // основное заглавие
                    461 // заглавие общей части
                ]),

            // авторы
            new RelevanceCoefficient(20, true,
                [
                    700, 701, // индивидуальные авторы
                    710, 711, 971, 972, // коллективные авторы
                ]),

            // выпуск, заглавие общей части
            new RelevanceCoefficient (10, false,
                [
                    923, // выпуск, часть
                    922, // статья сборника
                    925, // несколько томов в одной книге
                    961, // индивидуальные авторы общей части
                    962, // коллективы общей части
                    463 // издание, в котором опубликована статья
                ]),

            // редакторы
            new RelevanceCoefficient(7, false, [702]),

            // прочие заглавия
            new RelevanceCoefficient(6, false,
                [
                    510, // параллельное заглавие
                    517, // разночтение заглавия
                    541, // перевод заглавия
                    924, // "другое" заглавие
                    921 // транслитерированное заглавие
                ]),

            // содержание
            new RelevanceCoefficient(6, false,
                [
                    330, // оглавление
                    922 // статья из журнала
                ]),

            // рубрики
            new RelevanceCoefficient(5, false,
                [
                    606, // предметная рубрика
                    607, // географическая рубрика
                    600, 601, // персоналия
                    965 // дескриптор
                ]),

            // серия
            new RelevanceCoefficient(4, false, [225]),

            // ключевые слова и аннотации
            new RelevanceCoefficient(3, false,
                [
                    610, // ненормированное ключевое слово
                    331 // аннотация
                ])
        ];

        return $result;
    }

    /**
     * Загрузка настроек из указанного файла.
     *
     * @param $filename string Имя файла.
     * @return RelevanceSettings
     */
    public static function load($filename)
    {
        $text = file_get_contents($filename);
        $text = preg_replace( '![ \t]*//.*[ \t]*[\r\n]!', '', $text );
        return json_decode($text, false);
    }
}

/**
 * Оценщик релевантности найденных библиографических записей.
 */
final class RelevanceEvaluator
{
    /**
     * @var RelevanceSettings Настройки для оценки.
     */
    public $settings;

    /**
     * @var array Массив терминов, на которые разбивается поисковый запрос.
     */
    public $terms;

    /**
     * Оценка содержимого подполя.
     *
     * @param $text string Содержимое подполя.
     * @param $value double Важность поля.
     * @return double Оценка, выраженная числом.
     */
    private function evaluateText($text, $value) {
        // TODO возвращать признак полного совпадения

        $result = 0.0;

        if ($text) {
            foreach ($this->terms as $term) {
                if (stripos($text, $term) !== false) {
                    if (strcasecmp($text, $term) === 0) {
                        $result += $value * $this->settings->multiplier;
                    } else {
                        $result += $value;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Оценка содержимого поля.
     *
     * @param $field RecordField Поле, подоежащее оценке.
     * @param $value double Важность поля.
     * @return double Оценка, выраженная числом.
     */
    private function evaluateField ($field, $value) {
        // TODO возвращать признак полного совпадения
        $result = $this->evaluateText($field->value, $value);

        foreach ($field->subfields as $subfield) {
            $result += $this->evaluateText($subfield->value, $value);
        }

        return $result;
    }

    /**
     * Оценка реалеватности записи.
     *
     * @param $record MarcRecord Запись, подлежащая оценке.
     * @return double Оценка, выраженная числом.
     */
    public function evaluate($record) {
        // TODO обрабатывать признак полного совпадения и флаг приоритетного поиска

        $result = 0.0;

        foreach ($this->settings->coefficients as $coefficient) {
            foreach ($coefficient->fields as $tag) {
                $fields = $record->getFields($tag);
                foreach ($fields as $field) {
                    $result += $this->evaluateField($field, $coefficient->value);
                }
            }
        }

        foreach ($record->fields as $field) {
            $result += $this->evaluateField($field, $this->settings->extraneous);
        }

        return $result;
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
     * @var RelevanceSettings Настройки для оценки релевантности.
     */
    public $settings;

    /**
     * @var int Максимальное количество возвращаемых записей.
     */
    public $limit;

    /**
     * Конструктор.
     */
    public function __construct()
    {
        // поиск по: автору, заглавию, коллективу, ключевым словам
        $this->prefixes = array('A=', 'T=', 'M=', 'K=');
        $this->suffix = '$';
        $this->settings = RelevanceSettings::forIbis();
        $this->limit = 500;
    }

    /**
     * @var array Массив терминов.
     */
    private $terms;

    /**
     * Построение поискового выражения по запросу на естественном языке.
     *
     * @param $query string Запрос на естественном языке.
     * @return string Выражение для поиска по словарю.
     */
    public function buildSearchExpression($query)
    {
        $this->terms = [];

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
        $terms = array_keys($terms);
        sort($terms);
        foreach ($terms as $term) {
            if (isStopWord($term)) {
                continue;
            }

            $this->terms []= $term;
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

    /**
     * Поиск "для чайников" в текущей базе.
     *
     * @param $connection Connection Активное подключение к серверу.
     * @param $query string Запрос на естественном языке.
     * @return array Массив найденных MFN.
     */
    public function search($connection, $query) {
        $query = trim($query);
        if (!$query) {
            return [];
        }

        $expression = $this->buildSearchExpression($query);
        if (!$expression) {
            return [];
        }

        $found = $connection->searchRead($expression, $this->limit);
        if (!$found) {
            return [];
        }

        $evaluator = new RelevanceEvaluator();
        $evaluator->settings = $this->settings;
        $evaluator->terms = $this->terms;
        $rating = [];
        foreach ($found as $record) {
            $item = (object) array (
                'record' => $record,
                'rating' => $evaluator->evaluate($record)
            );
            $rating []= $item;
        }

        usort($rating, static function ($first, $second) {
            // сортировка по убыванию
            return $second->rating - $first->rating;
        });

        return array_map (static function ($item) {
            return $item->record->mfn;
        }, $rating);
    }
}
