<?php
/**
 * Created by PhpStorm.
 * User: sbraun
 * Date: 29.12.17
 * Time: 17:09
 */

class uuid_lib
{
select `ordered_uuid`(uuid());


select hex(uuid());
set @uuid = uuid();
select @uuid;
select replace(@uuid, '-','');
set @uuidbin = unhex(replace(@uuid, '-',''));
select @uuidbin;
select hex(@uuidbin);

select
UNHEX(CONCAT(SUBSTR(@uuid, 15, 4),SUBSTR(@uuid, 10, 4),SUBSTR(@uuid, 1, 8),SUBSTR(@uuid, 20, 4),SUBSTR(@uuid, 25)));

select
(CONCAT(SUBSTR(@uuid, 15, 4),'-',SUBSTR(@uuid, 10, 4),'-',SUBSTR(@uuid, 1, 8),'-',SUBSTR(@uuid, 20, 4),'-',SUBSTR(@uuid, 25))) as ordered_uuid
union
select
@uuid
;

select `ordered_uuid`(@uuid);
}