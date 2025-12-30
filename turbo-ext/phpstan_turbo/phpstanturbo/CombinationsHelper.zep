namespace PHPStanTurbo;

final class CombinationsHelper
{
    /**
     * @param array arrays
     * @return array
     */
    public static function combinations(array arrays) -> array
    {
        var head, elem, combination, c, comb, subResult, results;
        array remaining;

        if count(arrays) === 0 {
            return [[]];
        }

        let remaining = arrays;
        let head = array_shift(remaining);
        let results = [];

        for elem in head {
            let subResult = self::combinations(remaining);
            for combination in subResult {
                let comb = [elem];
                for c in combination {
                    let comb[] = c;
                }
                let results[] = comb;
            }
        }

        return results;
    }
}
