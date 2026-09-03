# Subfield type plugins
The following table provides an overview of the data type options available to
build a Custom Field.

| Plugin                                  | Description                                                                                                                                     |
|:----------------------------------------|:------------------------------------------------------------------------------------------------------------------------------------------------|
| [boolean](boolean.md)                   | Store a true or false value.                                                                                                                    |
| [color](color.md)                       | Store a hexadecimal color value.                                                                                                                |
| [datetime](datetime.md)                 | Store a date and optional timezone.                                                                                                             |
| [daterange](daterange.md)               | Store a duration consisting of start and end dates (and times) & optional timezone.                                                             |
| [duration](duration.md)                 | Store a calculated number of seconds as an integer.                                                                                             |
| [decimal](decimal.md)                   | Store a number in the database in a fixed decimal format. Ideal for exact counts and measures. (prices, temperatures, distances, volumes, etc.) |
| [email](email.md)                       | Store an email address value.                                                                                                                   |
| [entity_reference](entity-reference.md) | Store an entity id associated with a referenced entity.                                                                                         |
| [file](file.md)                         | Store an entity id associated with a file upload.                                                                                               |
| [float](float.md)                       | Store a number in the database in a floating point format.                                                                                      |
| [image](image.md)                       | Store an entity id & additional properties (height, width, alt) associated with an image upload.                                                |
| [integer](integer.md)                   | Store a number in the database as an integer.                                                                                                   |
| [link](link.md)                         | Store a URL string, optional varchar link text, and optional blob of attributes to assemble a link.                                             |
| [map](map.md)                           | Store a serialized array of values.                                                                                                             |
| [map_string](map-string.md)             | Store a serialized array of strings.                                                                                                            |
| [string](string.md)                     | Store a plain string value.                                                                                                                     |
| [string_long](string-long.md)           | Store a long text value.                                                                                                                        |
| [telephone](telephone.md)               | Store a telephone number.                                                                                                                       |
| [time](time.md)                         | Store a time value.                                                                                                                             |
| [time_range](time-range.md)             | Store a duration consisting of start and end times.                                                                                             |
| [uri](uri.md)                           | Store a URI string.                                                                                                                             |
| [uuid](uuid.md)                         | Store a UUID (unique identifier) value.                                                                                                         |
| [viewfield](viewfield.md)               | Store a reference to a view including a display, arguments & items to display. <br /> Requires the `custom_field_viewfield` sub-module.         |
