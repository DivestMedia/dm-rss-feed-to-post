<table class="form-table">
    <tbody>
        <?php foreach($this->option_fields[$meta['args']['group']] as $field => $data): ?>
            <tr class="user-rich-editing-wrap">
                <th scope="row">
                    <?=($data['label'])?>
                    <p class="description"><?=($data['description'])?></p>
                </th>
                <td>
                    <?php switch ($data['type']){
                        case 'grabber': ?>
                        <table>
                            <tbody>
                                <tr>
                                    <td>
                                        <p class="description"><strong>Loookup Method:</strong> </p>
                                        <select name="<?=($field)?>[]" id="<?=($field)?>-type">
                                            <?php foreach ([
                                                'ID','CSS','XPATH'
                                                ] as $option): ?>
                                                <option <?=($option==$data['value'][0]? 'selected' : '')?>><?=($option)?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="description">The type of DOM search</p>
                                    </td>
                                    <td>
                                        <p class="description"><strong>Search for:</strong></p>
                                        <input type="text" name="<?=($field)?>[]" id="<?=($field)?>-query" value="<?=esc_attr($data['value'][1])?>" class="regular-text">
                                        <p class="description">The query that will be used for searching</p>
                                    </td>
                                    <td>
                                        <p class="description"><strong>Grab the:</strong></p>
                                        <select name="<?=($field)?>[]" id="<?=($field)?>-grab">
                                            <?php foreach ([
                                                'Text',
                                                'HTML',
                                                'Input : Value',
                                                'Attr : src',
                                                'Attr : content',
                                                'Attr : href',
                                                'Attr : name',
                                                ] as $option):

                                                $optionslug = strtolower(preg_replace('/[^\da-z]/i', '', $option)); ?>
                                                <option value="<?=$optionslug?>" <?=($optionslug==$data['value'][2]? 'selected' : '')?>><?=($option)?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="description">The property of the element to grab</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <?php break;
                        case 'text': ?>
                        <?php default: ?>
                        <input type="text" name="<?=($field)?>" id="<?=($field)?>" value="<?=($data['value'])?>" class="regular-text">
                        <?php break; ?>
                        <?php } ?>
                        <?php
                        // Extra Details
                        switch ($field){
                            case '_rss_link':
                            $status = '';
                            if(!empty($data['value'])){
                                $rsslink = file_get_contents($data['value']);
                                try {
                                    $rss = new SimpleXmlElement($rsslink);
                                    if(isset($rss->channel->item)){
                                        $status = "RSS Valid." . $rss->channel->item->count() ." items found.";
                                    }else{
                                        $status = "RSS Valid but there are no items found.";
                                    }
                                }
                                catch(Exception $e){ $status = 'Invalid RSS'; }
                            }
                            ?>
                            <p class="description"><?=$status?></p>
                            <?php break; ?>
                            <?php } ?>
                        </td>
                    </tr>
                <?php endforeach;?>
            </tbody>

        </table>
        <input name="checker" type="button" class="button button-primary button-large" id="feed-check" value="Check Feed"><br><br>
        <table id="feed-details-area">
            <tbody>

            </tbody>
        </table>
        <textarea id="log-area" rows="8" class="hidden" style="width:100%"></textarea>
